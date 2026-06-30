<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Drapeaux de fonctionnalités (feature flags) pilotables sans déploiement.
 * NB : l'activation de la boutique reste portée par ShopSettings::$enabled (source unique boutique).
 */
class FeatureSettings extends Settings
{
    public bool $blog;

    public bool $pwa;

    public bool $online_payment;

    public static function group(): string
    {
        return 'features';
    }
}
