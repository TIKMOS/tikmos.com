<?php

declare(strict_types=1);

namespace App\Payments\Data;

use App\Payments\Enums\PaymentState;

/**
 * Résultat normalisé d'une initialisation ou d'une vérification de paiement.
 */
final readonly class ChargeResult
{
    /**
     * @param  int|null  $amount  Montant confirmé par la passerelle (contrôle serveur).
     * @param  array<string, mixed>  $raw  Payload brut de la passerelle (audit).
     */
    public function __construct(
        public PaymentState $state,
        public ?string $providerReference = null,
        public ?int $amount = null,
        public ?string $method = null,
        public array $raw = [],
    ) {}

    public function isSucceeded(): bool
    {
        return $this->state === PaymentState::Succeeded;
    }
}
