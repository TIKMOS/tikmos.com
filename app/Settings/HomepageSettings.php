<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HomepageSettings extends Settings
{
    public string $hero_kicker;

    public string $hero_title;

    public string $hero_subtitle;

    public ?string $hero_image;

    // Listes structurées (icône/titre/desc/…) — cast JSON brut spatie.
    public array $trust_badges;

    public array $services;

    public array $steps;

    public string $cta_title;

    public string $cta_text;

    /** @var array<string, bool> */
    public array $sections_enabled;

    /** @var array<int, string> */
    public array $sections_order;

    public static function group(): string
    {
        return 'homepage';
    }
}
