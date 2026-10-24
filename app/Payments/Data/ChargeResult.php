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
     * @param  array<string, mixed>  $raw  Données d'audit filtrées, sans secrets ni données client.
     * @param  string|null  $currency  Code ISO 4217 reçu, absent si la passerelle ne le fournit pas.
     * @param  string|null  $validationError  Motif interne d'un résultat non exploitable.
     */
    public function __construct(
        public PaymentState $state,
        public ?string $providerReference = null,
        public ?int $amount = null,
        public ?string $method = null,
        public array $raw = [],
        public ?string $currency = null,
        public ?string $validationError = null,
    ) {}

    public function isSucceeded(): bool
    {
        return $this->state === PaymentState::Succeeded;
    }
}
