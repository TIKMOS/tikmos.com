<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\DeliveryPricing;
use Spatie\LaravelSettings\Settings;

class ShopSettings extends Settings
{
    public bool $enabled;

    public string $currency;

    public int $default_delivery_fee;

    public bool $cod_enabled;

    public int $low_stock_threshold;

    public static function group(): string
    {
        return 'shop';
    }

    public function deliveryPricing(): DeliveryPricing
    {
        return match (true) {
            app(DeliverySettings::class)->mode === 'free' && $this->default_delivery_fee === 0 => DeliveryPricing::Free,
            app(DeliverySettings::class)->mode === 'fixed' && $this->default_delivery_fee > 0 => DeliveryPricing::Fixed,
            default => DeliveryPricing::Unknown,
        };
    }

    public function deliveryFee(): int
    {
        return $this->deliveryPricing() === DeliveryPricing::Fixed ? $this->default_delivery_fee : 0;
    }

    public function deliveryQuoteKey(): string
    {
        return hash('sha256', $this->deliveryPricing()->value.':'.$this->deliveryFee());
    }
}
