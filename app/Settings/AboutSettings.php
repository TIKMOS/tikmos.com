<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AboutSettings extends Settings
{
    public ?string $image;

    public string $title;

    public string $person_name;

    public string $hero_kicker;

    public string $subtitle;

    public string $professor_label;

    public string $specialty_label;

    public string $image_alt;

    public string $linkedin_url;

    public string $linkedin_label;

    public string $story_kicker;

    public string $values_kicker;

    public string $values_title;

    public string $cta_title;

    public string $quote_label;

    public string $quote_url;

    public string $services_label;

    public string $services_url;

    public bool $show_hero;

    public bool $show_story;

    public bool $show_values;

    public bool $show_cta;

    public bool $show_linkedin;

    public string $seo_title;

    public string $seo_description;

    public ?string $og_image;

    public static function group(): string
    {
        return 'about';
    }
}
