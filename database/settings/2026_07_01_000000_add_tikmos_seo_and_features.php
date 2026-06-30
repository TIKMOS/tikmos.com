<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // --- SEO global (méta par défaut + JSON-LD Person/Organization) ---
        $this->migrator->add('seo.default_title', 'Tikmos — Expertise, formation & solutions data/IA');
        $this->migrator->add('seo.default_description', "Tikmos : formation, conception de solutions informatiques, accompagnement et boutique de chaussures de luxe. L'expertise qui exécute.");
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.person_name', 'Le Professeur'); // TODO: vrai nom du fondateur
        $this->migrator->add('seo.person_job_title', 'Professeur certifié en mathématiques · Ingénieur Data Science & IA');
        $this->migrator->add('seo.person_url', null);
        $this->migrator->add('seo.organization_name', 'Tikmos');
        $this->migrator->add('seo.twitter_handle', null);

        // --- Feature flags (boutique = ShopSettings::$enabled, source unique) ---
        $this->migrator->add('features.blog', true);
        $this->migrator->add('features.pwa', false);
        $this->migrator->add('features.online_payment', false);

        // --- Recibler les coordonnées globales vers Tikmos (placeholders à compléter) ---
        $this->migrator->update('general.whatsapp_number', fn () => '2290000000000');
        $this->migrator->update('general.emails', fn () => [
            'contact' => 'contact@tikmos.com',
            'support' => 'support@tikmos.com',
        ]);
    }
};
