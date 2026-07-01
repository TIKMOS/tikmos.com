<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Aligne la description SEO sur le repositionnement boutique (équipements & accessoires de luxe).
        $this->migrator->update(
            'seo.default_description',
            fn () => "Tikmos : formation, conception de solutions informatiques, accompagnement et boutique d'équipements informatiques et accessoires de luxe. L'expertise qui exécute."
        );
    }
};
