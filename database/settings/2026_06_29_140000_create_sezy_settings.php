<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // --- Général (migre les valeurs V1 de config/sezy.php + layout/footer) ---
        $this->migrator->add('general.brand_phone', '+229 01 66 38 64 17');
        $this->migrator->add('general.whatsapp_number', '2290166386417');
        $this->migrator->add('general.phones', [
            '+229 01 66 38 64 17',
            '+229 0142 08 73 61',
            '+33 6 67 73 17 17',
        ]);
        $this->migrator->add('general.emails', [
            'contact' => 'contact@sezy.africa',
            'support' => 'support@sezy.africa',
        ]);
        $this->migrator->add('general.socials', [
            ['network' => 'instagram', 'url' => '#'],
            ['network' => 'facebook', 'url' => '#'],
            ['network' => 'linkedin', 'url' => '#'],
        ]);
        $this->migrator->add('general.top_bandeau_enabled', true);
        $this->migrator->add('general.top_bandeau_text', 'Cotonou ↔ Paris — présents des deux côtés du corridor');
        $this->migrator->add('general.addresses', [
            'cotonou' => 'Agontinkon, étoile rouge, 6ᵉ ruelle à droite — Cotonou, Bénin',
            'paris' => '4 rue Robert Schumann, 94350 Villiers-sur-Marne',
        ]);
        $this->migrator->add('general.business_hours', 'Lun – Ven · 9h – 18h (GMT+1)');
        $this->migrator->add('general.footer_tagline', "L'expertise logistique au service de la diaspora africaine.");
        $this->migrator->add('general.delivered_base', 1200);
        $this->migrator->add('general.nav_links', [
            ['label' => 'Services', 'path' => 'services', 'visible' => true],
            ['label' => 'Vols', 'path' => 'vols', 'visible' => true],
            ['label' => 'Suivi', 'path' => 'suivi', 'visible' => true],
            ['label' => 'À propos', 'path' => 'a-propos', 'visible' => true],
            ['label' => 'Contact', 'path' => 'contact', 'visible' => true],
        ]);

        // --- Accueil (migre le contenu en dur de home.blade.php) ---
        $this->migrator->add('homepage.hero_kicker', 'Cotonou ↔ Paris · 3–5 jours');
        $this->migrator->add('homepage.hero_title', "Vos colis & achats, d'un continent à l'autre.");
        $this->migrator->add('homepage.hero_subtitle', 'SEZY achète pour vous en France et expédie vos colis vers Cotonou. Suivez chaque étape, de l\'envoi à la livraison.');
        $this->migrator->add('homepage.hero_image', null);
        $this->migrator->add('homepage.trust_badges', [
            ['icon' => 'shield-check', 'label' => 'Assurance incluse'],
            ['icon' => 'wallet', 'label' => 'Paiement local'],
            ['icon' => 'route', 'label' => 'Suivi en temps réel'],
        ]);
        $this->migrator->add('homepage.services', [
            ['icon' => 'package', 'title' => 'Envoi de colis', 'desc' => 'Paris ↔ Cotonou en 3 à 5 jours, suivis à la trace.', 'href' => '/services', 'soon' => false, 'tint' => 'sky'],
            ['icon' => 'shopping-bag', 'title' => 'Achat & commande', 'desc' => 'On achète vos articles en France et on vous les livre en Afrique.', 'href' => '/achat', 'soon' => false, 'tint' => 'mint'],
            ['icon' => 'graduation-cap', 'title' => 'Mobilité & études', 'desc' => 'Accompagnement Campus France, logement, visa.', 'href' => '/contact', 'soon' => true, 'tint' => 'lilac'],
            ['icon' => 'dumbbell', 'title' => 'Boutique', 'desc' => 'Protéines et compléments sélectionnés, livrés près de chez vous.', 'href' => '/boutique', 'soon' => false, 'tint' => 'peach'],
        ]);
        $this->migrator->add('homepage.steps', [
            ['icon' => 'mouse-pointer-click', 'title' => 'Choisissez', 'desc' => 'Dites-nous ce que vous envoyez ou ce que vous voulez acheter.'],
            ['icon' => 'package-check', 'title' => 'On s\'occupe de tout', 'desc' => 'Collecte, achat, expédition — vous êtes tenu informé.'],
            ['icon' => 'route', 'title' => 'Suivez & recevez', 'desc' => 'Suivez chaque étape jusqu\'à la livraison.'],
        ]);
        $this->migrator->add('homepage.cta_title', 'Prêt à envoyer votre premier colis ?');
        $this->migrator->add('homepage.cta_text', 'Créez votre compte en moins d\'une minute et confiez-nous votre envoi.');
        $this->migrator->add('homepage.sections_enabled', [
            'marquee' => true, 'services' => true, 'steps' => true,
            'reviews' => true, 'faq' => true, 'partners' => true,
        ]);
        $this->migrator->add('homepage.sections_order', [
            'services', 'steps', 'reviews', 'faq', 'partners',
        ]);

        // --- Avis ---
        $this->migrator->add('reviews.delay_days', 7);
        $this->migrator->add('reviews.cooldown_days', 30);
        $this->migrator->add('reviews.min_rating_to_feature', 4);
        $this->migrator->add('reviews.auto_publish', false);

        // --- Boutique (désactivée tant que le lot boutique n'est pas livré) ---
        $this->migrator->add('shop.enabled', false);
        $this->migrator->add('shop.currency', 'XOF');
        $this->migrator->add('shop.default_delivery_fee', 0);
        $this->migrator->add('shop.cod_enabled', true);
        $this->migrator->add('shop.low_stock_threshold', 3);
    }
};
