<?php

declare(strict_types=1);

namespace App\Settings;

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
}
