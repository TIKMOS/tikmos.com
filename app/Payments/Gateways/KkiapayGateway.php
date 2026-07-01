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
use Illuminate\Support\Facades\Http;

/**
 * Passerelle KKiaPay (Mobile Money / Carte / Wave — zone UEMOA).
 *
 * - Le widget JS (cdn.kkiapay.me/k.js) encaisse côté client ; on récupère un transactionId.
 * - On VÉRIFIE ce transactionId côté serveur (source de vérité), jamais confiance au client.
 * - Le webhook (signé x-kkiapay-secret) confirme aussi de façon asynchrone (idempotent).
 *
 * @param  array{public_key: ?string, private_key: ?string, secret: ?string, sandbox: bool}  $config
 */
final class KkiapayGateway implements PaymentGateway
{
    /** @param array<string, mixed> $config */
    public function __construct(private array $config) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Kkiapay;
    }

    public function initialize(ChargeRequest $request): ChargeResult
    {
        // Rien à initialiser côté serveur : le widget est ouvert côté client avec la clé publique.
        // La référence est transmise via `data` puis rapprochée à la vérification/au webhook.
        return new ChargeResult(state: PaymentState::Pending, amount: $request->amount);
    }

    public function verify(string $providerReference): ChargeResult
    {
        $response = Http::withHeaders([
            'x-api-key' => (string) ($this->config['public_key'] ?? ''),
            'x-private-key' => (string) ($this->config['private_key'] ?? ''),
            'x-secret-key' => (string) ($this->config['secret'] ?? ''),
        ])->asJson()->post($this->baseUrl().'/api/v1/transactions/status', [
            'transactionId' => $providerReference,
        ]);

        $data = $response->json() ?? [];
        $status = strtoupper((string) ($data['status'] ?? ''));

        return new ChargeResult(
            state: $status === 'SUCCESS' ? PaymentState::Succeeded : PaymentState::Failed,
            providerReference: $providerReference,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            method: $this->method($data['source'] ?? null),
            raw: is_array($data) ? $data : [],
        );
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $expected = (string) ($this->config['secret'] ?? '');
        $received = (string) $request->header('x-kkiapay-secret', '');

        if ($expected === '' || ! hash_equals($expected, $received)) {
            return new WebhookResult(valid: false);
        }

        // Notre référence a été transmise via `data` du widget et renvoyée par KKiaPay.
        $meta = $request->input('data');
        $decoded = is_string($meta) ? json_decode($meta, true) : (is_array($meta) ? $meta : []);
        $reference = is_array($decoded) ? ($decoded['reference'] ?? null) : null;

        return new WebhookResult(
            valid: true,
            reference: is_string($reference) ? $reference : null,
            providerReference: (string) $request->input('transactionId') ?: null,
        );
    }

    private function baseUrl(): string
    {
        return ($this->config['sandbox'] ?? true)
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';
    }

    private function method(mixed $source): ?string
    {
        return match (strtoupper((string) $source)) {
            'MOBILE_MONEY', 'MOMO' => 'momo',
            'CARD' => 'card',
            'WALLET' => 'wallet',
            default => $source ? strtolower((string) $source) : null,
        };
    }
}
