<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReviewsSettings extends Settings
{
    public int $delay_days;

    public int $cooldown_days;

    public int $min_rating_to_feature;

    public bool $auto_publish;

    public static function group(): string
    {
        return 'reviews';
    }
}
