<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Réglages SEO globaux : méta par défaut + données structurées (JSON-LD Person/Organization).
 * Surchargés par page au lot 14 (SEO). Source unique de vérité pour le référencement.
 */
class SeoSettings extends Settings
{
    public string $default_title;

    public string $default_description;

    public ?string $og_image;

    // Person (JSON-LD) — [le Professeur]
    public string $person_name;

    public string $person_job_title;

    public ?string $person_url;

    // Organization (JSON-LD)
    public string $organization_name;

    public ?string $twitter_handle;

    public static function group(): string
    {
        return 'seo';
    }
}
