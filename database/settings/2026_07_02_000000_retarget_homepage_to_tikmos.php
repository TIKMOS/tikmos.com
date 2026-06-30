<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // --- Navigation & identité globale ---
        $this->migrator->update('general.nav_links', fn () => [
            ['label' => 'Services', 'path' => 'services', 'visible' => true],
            ['label' => 'Réalisations', 'path' => 'realisations', 'visible' => true],
            ['label' => 'Équipe', 'path' => 'equipe', 'visible' => true],
            ['label' => 'Blog', 'path' => 'blog', 'visible' => true],
            ['label' => 'À propos', 'path' => 'a-propos', 'visible' => true],
            ['label' => 'Contact', 'path' => 'contact', 'visible' => true],
        ]);
        $this->migrator->update('general.top_bandeau_text', fn () => 'Mathématiques · Data · IA — au service de vos projets');
        $this->migrator->update('general.footer_tagline', fn () => "L'expertise qui exécute : formation, solutions data/IA, et l'exigence du luxe.");
        $this->migrator->update('general.business_hours', fn () => 'Lun – Ven · 9h – 18h');

        // --- Accueil : héros « je suis / je propose » ---
        $this->migrator->update('homepage.hero_kicker', fn () => 'Mathématiques · Data · Intelligence Artificielle');
        $this->migrator->update('homepage.hero_title', fn () => 'Je conçois, je forme, je livre.');
        $this->migrator->update('homepage.hero_subtitle', fn () => "Professeur certifié en mathématiques et ingénieur data/IA. J'accompagne entreprises et particuliers — solutions sur mesure, formations, et une boutique de chaussures de luxe.");
        $this->migrator->update('homepage.trust_badges', fn () => [
            ['icon' => 'graduation-cap', 'label' => 'Professeur certifié'],
            ['icon' => 'cpu', 'label' => 'Ingénieur Data / IA'],
            ['icon' => 'users', 'label' => 'Une équipe dédiée'],
        ]);

        // --- Accueil : métiers (cartes services) ---
        $this->migrator->update('homepage.services', fn () => [
            ['icon' => 'graduation-cap', 'title' => 'Formation', 'desc' => 'Formations entreprises : informatique, suites logicielles, data & IA appliquée.', 'href' => '/services', 'soon' => false, 'tint' => 'bg-indigo-50'],
            ['icon' => 'layers', 'title' => 'Solutions informatiques', 'desc' => 'Conception de solutions sur mesure et pilotage de projets data / IA.', 'href' => '/services', 'soon' => false, 'tint' => 'bg-gold-100'],
            ['icon' => 'wrench', 'title' => "Services d'appui", 'desc' => 'Installation, assistance administrative, orientation professionnelle.', 'href' => '/services', 'soon' => false, 'tint' => 'bg-sand'],
            ['icon' => 'shopping-bag', 'title' => 'Boutique de luxe', 'desc' => 'Chaussures de luxe sélectionnées, commande simple et suivie.', 'href' => '/boutique', 'soon' => false, 'tint' => 'bg-mist'],
        ]);

        // --- Accueil : approche (étapes) ---
        $this->migrator->update('homepage.steps', fn () => [
            ['icon' => 'compass', 'title' => 'Cadrage', 'desc' => "On clarifie votre besoin et l'objectif, sans jargon."],
            ['icon' => 'pen-tool', 'title' => 'Conception', 'desc' => 'On conçoit la solution ou le programme de formation, sur mesure.'],
            ['icon' => 'package-check', 'title' => 'Livraison & suivi', 'desc' => 'On livre, on forme, et on accompagne dans la durée.'],
        ]);

        $this->migrator->update('homepage.cta_title', fn () => 'Un projet, une formation, une paire en vue ?');
        $this->migrator->update('homepage.cta_text', fn () => 'Décrivez votre besoin : réponse rapide, devis clair, accompagnement humain.');

        // Ordre des sections de l'accueil (ajout « team »).
        $this->migrator->update('homepage.sections_order', fn () => ['services', 'steps', 'team', 'reviews', 'faq']);
        $this->migrator->update('homepage.sections_enabled', fn ($v) => array_merge((array) $v, ['team' => true]));
    }
};
