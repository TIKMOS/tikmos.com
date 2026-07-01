<?php

declare(strict_types=1);

namespace App\Payments\Data;

/**
 * Requête d'encaissement, indépendante du métier (portable).
 */
final readonly class ChargeRequest
{
    /**
     * @param  int  $amount  Montant dans la plus petite unité (XOF entier).
     * @param  array<string, mixed>  $metadata  Données libres (ex. numéro de commande).
     */
    public function __construct(
        public string $reference,
        public int $amount,
        public string $currency = 'XOF',
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $callbackUrl = null,
        public array $metadata = [],
    ) {}
}
