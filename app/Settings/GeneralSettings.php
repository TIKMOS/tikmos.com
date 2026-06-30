<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $brand_phone;

    public string $whatsapp_number;

    /** @var array<int, string> */
    public array $phones;

    /** @var array<string, string> */
    public array $emails;

    // Liste de [network, url] — typage volontairement souple (cast JSON brut spatie).
    public array $socials;

    public bool $top_bandeau_enabled;

    public string $top_bandeau_text;

    /** @var array<string, string> */
    public array $addresses;

    public string $business_hours;

    public string $footer_tagline;

    public int $delivered_base;

    // Liste de [label, path, visible] — cast JSON brut.
    public array $nav_links;

    public static function group(): string
    {
        return 'general';
    }
}
