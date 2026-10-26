<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\ChargeRequest;
use App\Payments\Data\ChargeResult;
use App\Payments\Data\WebhookResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use Illuminate\Http\Client\ConnectionException;
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
        try {
            $response = Http::timeout(15)->withHeaders([
                'x-api-key' => (string) ($this->config['public_key'] ?? ''),
                'x-private-key' => (string) ($this->config['private_key'] ?? ''),
                'x-secret-key' => (string) ($this->config['secret'] ?? ''),
            ])->asJson()->post($this->baseUrl().'/api/v1/transactions/status', [
                'transactionId' => $providerReference,
            ]);
        } catch (ConnectionException) {
            return new ChargeResult(state: PaymentState::Pending, validationError: 'verification_unavailable');
        }

        $body = $response->json();
        $data = is_array($body) ? $body : [];
        $status = is_string($data['status'] ?? null) ? strtoupper($data['status']) : '';
        $amount = $this->amount($data['amount'] ?? null);
        $currency = $data['currency'] ?? null;
        $currency = is_string($currency) && preg_match('/\A[A-Z]{3}\z/', $currency) === 1 ? $currency : null;

        $validationError = match (true) {
            ! $response->successful() => 'verification_unavailable',
            ! in_array($status, ['SUCCESS', 'FAILED', 'PENDING'], true) => 'verification_incomplete',
            $status === 'SUCCESS' => match (true) {
                ! isset($data['amount']) => 'amount_missing',
                $amount === null => 'amount_invalid',
                array_key_exists('currency', $data) && $currency === null => 'currency_invalid',
                default => null,
            },
            default => null,
        };

        $state = $validationError !== null ? PaymentState::Pending : match ($status) {
            'SUCCESS' => PaymentState::Succeeded,
            'FAILED' => PaymentState::Failed,
            default => PaymentState::Pending,
        };

        return new ChargeResult(
            state: $state,
            providerReference: $providerReference,
            amount: $amount,
            method: $this->method($data['source'] ?? null),
            raw: [
                'http_status' => $response->status(),
                'status' => in_array($status, ['SUCCESS', 'FAILED', 'PENDING'], true) ? $status : 'UNKNOWN',
                'amount' => $amount,
                'currency' => $currency,
            ],
            currency: $currency,
            validationError: $validationError,
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

    /** Montant XOF entier, sans conversion silencieuse de fraction, booléen ou dépassement. */
    private function amount(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_float($value)) {
            return is_finite($value) && $value >= 0 && floor($value) === $value && $value < PHP_INT_MAX
                ? (int) $value
                : null;
        }

        if (! is_string($value) || $value === '' || ! ctype_digit($value)) {
            return null;
        }

        $digits = ltrim($value, '0');
        $maximum = (string) PHP_INT_MAX;
        if (strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            return null;
        }

        return (int) $value;
    }

    private function method(mixed $source): ?string
    {
        return match (is_string($source) ? strtoupper($source) : '') {
            'MOBILE_MONEY', 'MOMO' => 'momo',
            'CARD' => 'card',
            'WALLET' => 'wallet',
            default => null,
        };
    }
}
