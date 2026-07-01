<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Payments\Data\ChargeRequest;
use App\Payments\Data\ChargeResult;
use App\Payments\Data\WebhookResult;
use App\Payments\Enums\PaymentProvider;
use Illuminate\Http\Request;

/**
 * Contrat commun à toutes les passerelles de paiement (portable).
 */
interface PaymentGateway
{
    public function provider(): PaymentProvider;

    /** Prépare/initie une charge (rendu widget ou redirection selon la passerelle). */
    public function initialize(ChargeRequest $request): ChargeResult;

    /** Vérifie une transaction côté serveur (source de vérité). */
    public function verify(string $providerReference): ChargeResult;

    /** Valide la signature d'un webhook et normalise l'événement. */
    public function handleWebhook(Request $request): WebhookResult;
}
