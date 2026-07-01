<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    /** 'cod' | 'online' | 'both' */
    public string $mode;

    public bool $kkiapay_sandbox;

    public string $kkiapay_public_key;

    public string $kkiapay_private_key;

    public string $kkiapay_secret;

    public static function group(): string
    {
        return 'payments';
    }

    /** Clés sensibles chiffrées au repos. */
    public static function encrypted(): array
    {
        return ['kkiapay_private_key', 'kkiapay_secret'];
    }

    public function onlineEnabled(): bool
    {
        return in_array($this->mode, ['online', 'both'], true);
    }

    public function codEnabled(): bool
    {
        return in_array($this->mode, ['cod', 'both'], true);
    }

    /**
     * Surcharge de config('payments.gateways.kkiapay') par les réglages admin
     * (les valeurs vides laissent le fallback env/config en place).
     *
     * @return array<string, mixed>
     */
    public function kkiapayConfig(): array
    {
        return array_filter([
            'public_key' => $this->kkiapay_public_key ?: null,
            'private_key' => $this->kkiapay_private_key ?: null,
            'secret' => $this->kkiapay_secret ?: null,
            'sandbox' => $this->kkiapay_sandbox,
        ], static fn ($value): bool => $value !== null);
    }
}
