<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Navigation : À propos · Services · Blog (Boutique ajoutée après via ShopSettings).
        // Réalisations, Équipe et Contact sont masqués du menu.
        $this->migrator->update('general.nav_links', fn () => [
            ['label' => 'À propos', 'path' => 'a-propos', 'visible' => true],
            ['label' => 'Services', 'path' => 'services', 'visible' => true],
            ['label' => 'Blog', 'path' => 'blog', 'visible' => true],
        ]);

        // Coordonnées.
        $this->migrator->update('general.whatsapp_number', fn () => '2290197440615');
        $this->migrator->update('general.brand_phone', fn () => '+229 01 97 44 06 15');
        $this->migrator->update('general.phones', fn () => ['+229 01 97 44 06 15']);

        // CTA d'accueil.
        $this->migrator->update('homepage.cta_title', fn () => 'Un projet ambitieux, une formation en vue ?');
    }
};
