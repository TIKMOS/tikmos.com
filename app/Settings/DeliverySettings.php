<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DeliverySettings extends Settings
{
    public string $mode;

    public static function group(): string
    {
        return 'delivery';
    }
}
