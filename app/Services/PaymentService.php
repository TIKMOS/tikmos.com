<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Payments\Contracts\Payable;
use App\Payments\Data\ChargeRequest;
use App\Payments\Data\ChargeResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use App\Payments\PaymentManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        /** @var Payment|null $existing */
        $existing = $payable->morphMany(Payment::class, 'payable')
            ->where('provider', $provider->value)
            ->where('state', PaymentState::Pending->value)
            ->latest()
            ->first();

        if ($existing) {
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
                /** @var Payment $locked */
                $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

                if (! $result->isSucceeded()) {
                    $locked->markFailed($result);

                    return false;
                }

                // L'index unique arbitre aussi les appels concurrents sur deux paiements.
                if (! $locked->markSucceeded($result)) {
                    return false;
                }

                $payable = $locked->payable;
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
