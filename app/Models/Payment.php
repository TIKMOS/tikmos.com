<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Concerns\Auditable;
use App\Payments\Data\ChargeResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Transaction de paiement rattachée polymorphiquement à un payable (commande, devis…).
 *
 * @property PaymentState $state
 * @property int $amount
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $paid_at
 */
#[Fillable([
    'payable_type', 'payable_id', 'provider', 'reference', 'provider_reference',
    'amount', 'currency', 'state', 'method', 'meta', 'paid_at',
])]
class Payment extends Model
{
    use Auditable;

    /** @return array<int, string> */
    protected function auditAttributes(): array
    {
        return ['state', 'method', 'amount', 'paid_at'];
    }

    protected function auditGroup(): string
    {
        return 'paiement';
    }

    protected function casts(): array
    {
        return [
            'state' => PaymentState::class,
            'amount' => 'integer',
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    /**
     * Journalise un événement de cycle de vie (audit).
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordEvent(string $type, ?PaymentState $state = null, array $payload = []): PaymentEvent
    {
        return $this->events()->create([
            'type' => $type,
            'state' => $state?->value,
            'payload' => $payload,
        ]);
    }

    public function isSucceeded(): bool
    {
        return $this->state === PaymentState::Succeeded;
    }

    /**
     * Passe la transaction à « payé » de façon IDEMPOTENTE.
     *
     * Retourne true uniquement lors de la première transition réussie
     * (le webhook et le retour widget peuvent tous deux appeler cette méthode).
     * La référence externe, une fois renseignée, ne peut pas être remplacée.
     * Le service doit appeler cette méthode sous transaction et verrou de ligne.
     * Contrôle du montant : refus si le montant fourni diffère.
     */
    public function markSucceeded(ChargeResult $result): bool
    {
        if ($this->isSucceeded()) {
            return false;
        }

        $providerReference = $result->providerReference ?? $this->provider_reference;

        if ($this->provider === PaymentProvider::Kkiapay->value && ($providerReference === null || trim($providerReference) === '')) {
            $this->recordEvent('provider_reference_missing', $this->state);

            return false;
        }

        if ($this->provider_reference !== null && $this->provider_reference !== $providerReference) {
            $this->recordEvent('provider_reference_mismatch', $this->state, [
                'expected' => $this->provider_reference,
                'received' => $providerReference,
            ]);

            return false;
        }

        if ($result->amount !== null && $result->amount !== $this->amount) {
            $this->recordEvent('amount_mismatch', $this->state, [
                'expected' => $this->amount,
                'received' => $result->amount,
            ]);

            return false;
        }

        $this->update([
            'state' => PaymentState::Succeeded,
            'provider_reference' => $providerReference,
            'method' => $result->method ?? $this->method,
            'paid_at' => now(),
        ]);

        $this->recordEvent('succeeded', PaymentState::Succeeded, $result->raw);

        return true;
    }

    public function markFailed(ChargeResult $result): void
    {
        if ($this->state->isFinal()) {
            return;
        }

        $this->update(['state' => PaymentState::Failed]);
        $this->recordEvent('failed', PaymentState::Failed, $result->raw);
    }
}
