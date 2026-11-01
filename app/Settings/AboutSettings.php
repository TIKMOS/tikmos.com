<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AboutSettings extends Settings
{
    public ?string $image;

    public static function group(): string
    {
        return 'about';
    }
}
