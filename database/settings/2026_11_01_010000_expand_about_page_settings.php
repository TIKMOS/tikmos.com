<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // add() keeps an existing saved value, so this migration never overwrites admin content.
        $defaults = [
            'title' => "L'expertise qui exécute.",
            'person_name' => 'Moïse TIKO',
            'hero_kicker' => 'À propos',
            'subtitle' => "Des mathématiques à la data science, de la salle de classe aux projets d'entreprise : une seule exigence, livrer des résultats concrets.",
            'professor_label' => 'Professeur certifié',
            'specialty_label' => 'Ingénieur Data / IA',
            'image_alt' => 'Photo de Moïse TIKO',
            'linkedin_url' => 'https://www.linkedin.com/in/tiko-moise/',
            'linkedin_label' => 'Voir mon profil LinkedIn',
            'story_kicker' => 'Mon parcours',
            'values_kicker' => 'Ce qui me guide',
            'values_title' => 'Des valeurs simples, tenues.',
            'cta_title' => 'Travaillons ensemble.',
            'quote_label' => 'Demander un devis',
            'quote_url' => '/demander-un-devis',
            'services_label' => 'Mes services',
            'services_url' => '/services',
            'show_hero' => true,
            'show_story' => true,
            'show_values' => true,
            'show_cta' => true,
            'show_linkedin' => true,
            'seo_title' => 'À propos de Moïse TIKO | Tikmos',
            'seo_description' => "Professeur certifié en mathématiques et ingénieur data science & IA : l'expertise qui exécute, au service de vos projets, vos formations et vos achats.",
            'og_image' => null,
        ];

        foreach ($defaults as $name => $value) {
            $property = 'about.'.$name;
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $value);
            }
        }
    }
};
