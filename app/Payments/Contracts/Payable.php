<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Payment;

/**
 * Tout modèle payable (commande, devis…) implémente ce contrat pour réagir
 * à un encaissement confirmé — sans coupler le module de paiement au métier.
 */
interface Payable
{
    /** Montant attendu (XOF entier), recalculé côté serveur. */
    public function payableAmount(): int;

    /** Réaction idempotente au paiement confirmé (statut, notifications…). */
    public function onPaymentSucceeded(Payment $payment): void;
}
