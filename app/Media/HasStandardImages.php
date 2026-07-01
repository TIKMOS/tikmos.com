<?php

declare(strict_types=1);

namespace App\Media;

use App\Settings\MediaSettings;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Conversions d'images homogènes sur tout le site (WebP, tailles responsives).
 *
 * Trois tailles standard, générées pour TOUTES les collections du modèle :
 *   - thumb (160)  : vignettes admin, avatars.
 *   - card  (800)  : cartes, listes, contenu courant.
 *   - full  (1600) : plein écran / hero / détail.
 *
 * Le modèle garde la responsabilité de déclarer SES collections
 * (`registerMediaCollections`). Il suffit d'ajouter `use HasStandardImages`
 * et de supprimer tout `registerMediaConversions` local.
 */
trait HasStandardImages
{
    /** @var array<string, int> largeur de chaque conversion (utilisée pour srcset). */
    public static array $standardImageWidths = [
        'thumb' => 160,
        'card' => 800,
        'full' => 1600,
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $quality = rescue(fn (): int => app(MediaSettings::class)->quality, 82, false);

        foreach (static::$standardImageWidths as $name => $width) {
            // Fit::Max : réduit dans un cadre carré en conservant le ratio (jamais d'agrandissement,
            // pas de recadrage destructeur — convient à toutes les collections).
            $this->addMediaConversion($name)
                ->nonQueued()
                ->format('webp')
                ->quality($quality)
                ->fit(Fit::Max, $width, $width);
        }
    }
}
