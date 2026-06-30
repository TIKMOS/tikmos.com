<?php

/*
| Catalogue des permissions — source de vérité, consommée par RolePermissionSeeder.
| '*' = toutes les permissions. Voir docs/02-stack-et-auth.md §3 et docs/21-data-model-v2.md §5.
*/

return [
    'permissions' => [
        // --- V1 ---
        // Colis (domaine Sezy hérité — retiré avec son métier ultérieurement)
        'colis.view', 'colis.create', 'colis.update', 'colis.delete', 'colis.advance', 'colis.export',
        // Vols (domaine Sezy hérité)
        'vol.view', 'vol.manage',
        // Clients
        'client.view', 'client.manage',
        // Demandes / devis (services Tikmos)
        'demande.view', 'demande.manage', 'demande.assign', 'quotes.send',
        // Offre de services & réalisations (Tikmos)
        'services.view', 'services.manage', 'projects.manage',
        // Blog / actualités (Tikmos)
        'blog.manage',
        // Messages (contact)
        'message.view',
        // Équipe & administration
        'equipe.view', 'equipe.manage',
        'settings.manage',
        'audit.view',
        'roles.manage',

        // --- V2 : CMS & configuration ---
        'cms.homepage.manage', 'cms.faq.manage', 'cms.pages.manage',
        'preferred_sites.manage', 'partners.manage', 'team.manage',
        // V2 : avis
        'reviews.view', 'reviews.moderate', 'reviews.feature',
        // V2 : gestion utilisateurs (personnel + clients)
        'users.manage', 'clients.manage',
        // V2 : boutique & commandes
        'shop.products.manage', 'shop.categories.manage', 'shop.orders.view', 'shop.orders.update',
        // V2 : livraison & transporteurs
        'couriers.manage', 'deliveries.view', 'deliveries.assign', 'deliveries.update', 'deliveries.own',
    ],

    'roles' => [
        'super_admin' => '*',

        'admin' => [
            'colis.view', 'colis.create', 'colis.update', 'colis.delete', 'colis.advance', 'colis.export',
            'vol.view', 'vol.manage',
            'client.view', 'client.manage',
            'demande.view', 'demande.manage', 'demande.assign', 'quotes.send',
            'services.view', 'services.manage', 'projects.manage',
            'blog.manage',
            'message.view',
            'equipe.view', 'equipe.manage',
            'settings.manage',
            'audit.view',
            // V2
            'cms.homepage.manage', 'cms.faq.manage', 'cms.pages.manage',
            'preferred_sites.manage', 'partners.manage', 'team.manage',
            'reviews.view', 'reviews.moderate', 'reviews.feature',
            'users.manage', 'clients.manage',
            'shop.products.manage', 'shop.categories.manage', 'shop.orders.view', 'shop.orders.update',
            'couriers.manage', 'deliveries.view', 'deliveries.assign', 'deliveries.update',
        ],

        // Commercial — devis/demandes, clients, services (pas de config ni de gestion d'équipe).
        'commercial' => [
            'demande.view', 'demande.manage', 'demande.assign', 'quotes.send',
            'services.view',
            'client.view', 'client.manage',
            'message.view',
            'reviews.view',
        ],

        // Éditeur — contenu/CMS uniquement (pas d'accès financier ni clients).
        'editor' => [
            'cms.homepage.manage', 'cms.faq.manage', 'cms.pages.manage',
            'services.manage', 'projects.manage', 'blog.manage',
            'partners.manage', 'team.manage',
            'reviews.view',
        ],

        'gestionnaire_boutique' => [
            'shop.products.manage', 'shop.categories.manage', 'shop.orders.view', 'shop.orders.update',
            'reviews.view', 'reviews.moderate',
            'deliveries.view', 'deliveries.assign',
        ],

        'agent_cotonou' => [
            'colis.view', 'colis.create', 'colis.update', 'colis.advance',
            'client.view',
            'demande.view',
            'deliveries.view', 'deliveries.update',
        ],

        'agent_paris' => [
            'colis.view', 'colis.update', 'colis.advance',
            'vol.view', 'vol.manage',
            'client.view',
            'deliveries.view', 'deliveries.update',
        ],

        'conseiller_mobilite' => [
            'client.view',
        ],

        'support' => [
            'colis.view',
            'client.view',
            'demande.view', 'demande.manage',
            'message.view',
            'reviews.view',
        ],

        'livreur' => [
            'deliveries.own',
        ],

        'client' => [],
    ],
];
