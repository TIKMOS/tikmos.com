<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\ChargeRequest;
use App\Payments\Data\ChargeResult;
use App\Payments\Data\WebhookResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use Illuminate\Http\Request;

/**
 * Paiement à la livraison (Cash On Delivery) — encaissement confirmé par l'admin à la remise.
 */
final class CodGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Cod;
    }

    public function initialize(ChargeRequest $request): ChargeResult
    {
        return new ChargeResult(state: PaymentState::Pending, amount: $request->amount, method: 'cash');
    }

    public function verify(string $providerReference): ChargeResult
    {
        // Le COD n'est pas vérifiable par une passerelle : il reste en attente
        // jusqu'à l'encaissement manuel (admin) à la livraison.
        return new ChargeResult(state: PaymentState::Pending, method: 'cash');
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        return new WebhookResult(valid: false);
    }
}
