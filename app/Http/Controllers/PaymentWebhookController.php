<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réception des webhooks de paiement (signés). Répond toujours 200 pour
 * éviter les rejeux inutiles ; la vérification serveur garantit l'intégrité.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentService $payments): JsonResponse
    {
        $handled = $payments->handleWebhook($provider, $request);

        return response()->json(['handled' => $handled]);
    }
}
