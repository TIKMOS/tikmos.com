<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Contracts\Payable;
use App\Payments\Data\ChargeRequest;
use App\Payments\Data\ChargeResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use App\Payments\PaymentManager;
use App\Settings\PaymentSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Orchestration entre passerelles (PaymentManager) et modèles (Payment/Payable).
 *
 * Toute la logique idempotente/sécurité vit ici : la source de vérité est
 * toujours une vérification serveur (verify) ou un webhook signé.
 */
class PaymentService
{
    public function __construct(private PaymentManager $manager) {}

    /**
     * Ouvre (ou retrouve) une transaction en attente pour un payable donné.
     */
    public function open(Payable&Model $payable, PaymentProvider $provider): Payment
    {
        return DB::transaction(function () use ($payable, $provider): Payment {
            // Même ordre de verrouillage pour ouverture, règlement et expiration.
            $payable = $payable->newQuery()->whereKey($payable->getKey())->lockForUpdate()->firstOrFail();
            if (! $payable instanceof Payable) {
                throw new \LogicException('Le modèle relu ne prend pas en charge les paiements.');
            }
            if ($payable instanceof Order && $payable->payment_status !== OrderPaymentStatus::Unpaid) {
                throw ValidationException::withMessages(['payment' => 'Cette commande a déjà été réglée.']);
            }

            return $this->openLocked($payable, $provider);
        }, attempts: 3);
    }

    public function resumeOrder(Order $order, User $user): Payment
    {
        $payment = DB::transaction(function () use ($order, $user): ?Payment {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->user_id === $user->id, 403);
            app(OrderReservationService::class)->releaseIfDue($locked);
            if (! $locked->canResumePayment() || ! app(PaymentSettings::class)->onlineEnabled()) {
                return null;
            }

            return $this->openLocked($locked, PaymentProvider::Kkiapay);
        }, attempts: 3);
        // Refuser après commit pour conserver une restitution devenue nécessaire.
        if ($payment === null) {
            if (! $order->fresh()->deliveryIsFinal()) {
                throw ValidationException::withMessages(['payment' => 'Les frais de livraison et le montant final doivent être validés avant un paiement en ligne. Contactez notre équipe avec votre référence de commande.']);
            }
            throw ValidationException::withMessages(['payment' => 'Paiement indisponible ou réservation expirée. Consultez l’état de la commande et contactez l’équipe si vous avez été débité.']);
        }

        return $payment;
    }

    private function openLocked(Payable&Model $payable, PaymentProvider $provider): Payment
    {
        if ($payable instanceof Order && $provider->isOnline() && ! $payable->deliveryIsFinal()) {
            throw ValidationException::withMessages(['payment' => 'Les frais de livraison et le montant final doivent être validés avant un paiement en ligne. Contactez notre équipe avec votre référence de commande.']);
        }
        /** @var Payment|null $existing */
        $existing = $payable->morphMany(Payment::class, 'payable')
            ->where('provider', $provider->value)
            ->whereIn('state', $payable instanceof Order
                ? [PaymentState::Pending->value, PaymentState::Failed->value]
                : [PaymentState::Pending->value])
            ->latest()
            ->first();

        if ($existing) {
            if ($existing->amount !== $payable->payableAmount() || $existing->currency !== config('payments.currency', 'XOF')) {
                throw ValidationException::withMessages(['payment' => 'Le montant du paiement nécessite une vérification par l’équipe.']);
            }

            return $existing;
        }

        $amount = $payable->payableAmount();

        $payment = $payable->morphMany(Payment::class, 'payable')->create([
            'provider' => $provider->value,
            'reference' => $this->reference($provider),
            'amount' => $amount,
            'currency' => (string) config('payments.currency', 'XOF'),
            'state' => PaymentState::Pending,
        ]);

        $result = $this->manager->gateway($provider)->initialize(new ChargeRequest(
            reference: $payment->reference,
            amount: $amount,
        ));

        $payment->recordEvent('initialized', $result->state);

        return $payment;
    }

    /**
     * Vérifie une transaction côté serveur (retour widget) puis applique le résultat.
     */
    public function verify(Payment $payment, string $providerReference): bool
    {
        $result = $this->manager->resolveOrFail($payment->provider)->verify($providerReference);
        $payment->recordEvent('verified', $result->state, $result->raw);

        return $this->settle($payment, $result);
    }

    /**
     * Traite un webhook signé : re-vérifie côté serveur avant d'appliquer.
     */
    public function handleWebhook(string $provider, Request $request): bool
    {
        $gateway = $this->manager->resolveOrFail($provider);
        $hook = $gateway->handleWebhook($request);

        if (! $hook->valid || $hook->reference === null) {
            return false;
        }

        /** @var Payment|null $payment */
        $payment = Payment::where('reference', $hook->reference)->first();

        if ($payment === null) {
            return false;
        }

        if ($payment->provider !== $provider) {
            $payment->recordEvent('provider_mismatch', $payment->state, ['received' => $provider]);

            return false;
        }

        // On ne fait jamais confiance au payload : on re-vérifie la transaction.
        $reference = $hook->providerReference ?? $payment->provider_reference;
        if ($reference === null) {
            return false;
        }

        $result = $gateway->verify($reference);
        $payment->recordEvent('webhook', $result->state, ['provider_reference' => $reference]);

        return $this->settle($payment, $result);
    }

    /**
     * Applique un résultat de passerelle à la transaction (idempotent) et
     * déclenche la réaction métier une seule fois.
     */
    public function settle(Payment $payment, ChargeResult $result): bool
    {
        try {
            return DB::transaction(function () use ($payment, $result): bool {
                // L'expiration verrouille aussi la commande en premier : pas de course
                // entre restitution du stock et confirmation d'un paiement.
                $order = $payment->payable_type === (new Order)->getMorphClass()
                    ? Order::whereKey($payment->payable_id)->lockForUpdate()->firstOrFail()
                    : null;
                /** @var Payment $locked */
                $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

                if ($result->validationError !== null || $result->state === PaymentState::Pending) {
                    if (! $locked->state->isFinal()) {
                        $locked->recordEvent($result->validationError ?? 'verification_pending', $locked->state);
                    }

                    return false;
                }

                if (! $result->isSucceeded()) {
                    $locked->markFailed($result);

                    return false;
                }

                // L'index unique arbitre aussi les appels concurrents sur deux paiements.
                if (! $locked->markSucceeded($result)) {
                    return false;
                }

                $payable = $order ?? $locked->payable;
                if ($payable instanceof Payable) {
                    $payable->onPaymentSucceeded($locked);
                }

                return true;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            // SQLite fournit les colonnes ; MySQL fournit le nom de l'index.
            // Toute autre violation reste une erreur, pas un conflit de paiement.
            if ($exception->index !== 'payments_provider_reference_unique'
                && $exception->columns !== ['provider', 'provider_reference']) {
                throw $exception;
            }

            // Journaliser après rollback : aucun statut, audit de succès ou effet métier ne subsiste.
            $payment->refresh();
            $payment->recordEvent('provider_reference_conflict', $payment->state, [
                'provider' => $payment->provider,
                'provider_reference' => $result->providerReference ?? $payment->provider_reference,
            ]);

            return false;
        }
    }

    private function reference(PaymentProvider $provider): string
    {
        return strtoupper($provider->value[0]).'-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
    }
}
