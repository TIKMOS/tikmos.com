<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MediaSettings extends Settings
{
    /** @var array<int, string> extensions acceptées (sans le point). */
    public array $accepted_formats;

    public int $max_size_kb;

    public int $quality;

    /** Placeholder flou pendant le chargement (LQIP) sur la vitrine. */
    public bool $blur_up;

    /** Nombre max d'images par galerie. */
    public int $gallery_max;

    public static function group(): string
    {
        return 'media';
    }

    /** Règle de validation `mimes:` dérivée des formats acceptés. */
    public function mimesRule(): string
    {
        return 'mimes:'.implode(',', $this->accepted_formats);
    }
}
