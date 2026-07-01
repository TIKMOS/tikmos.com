<?php

/*
| Catalogue des permissions — source de vérité, consommée par RolePermissionSeeder.
| '*' = toutes les permissions. Voir docs/02-stack-et-auth.md §3.
*/

return [
    'permissions' => [
        // Clients
        'client.view', 'client.manage',
        // Demandes / devis (services Tikmos)
        'demande.view', 'demande.manage', 'demande.assign', 'quotes.send',
        // Offre de services & réalisations
        'services.view', 'services.manage', 'projects.manage',
        // Blog / actualités
        'blog.manage',
        // Messages (contact)
        'message.view',
        // Équipe & administration
        'equipe.view', 'equipe.manage',
        'settings.manage',
        'audit.view',
        'roles.manage',
        // CMS & configuration
        'cms.homepage.manage', 'cms.faq.manage', 'cms.pages.manage',
        'partners.manage', 'team.manage',
        // Avis
        'reviews.view', 'reviews.moderate', 'reviews.feature',
        // Gestion utilisateurs
        'users.manage', 'clients.manage',
        // Boutique & commandes
        'shop.products.manage', 'shop.categories.manage', 'shop.orders.view', 'shop.orders.update',
    ],

    'roles' => [
        'super_admin' => '*',

        'admin' => [
            'client.view', 'client.manage',
            'demande.view', 'demande.manage', 'demande.assign', 'quotes.send',
            'services.view', 'services.manage', 'projects.manage',
            'blog.manage',
            'message.view',
            'equipe.view', 'equipe.manage',
            'settings.manage',
            'audit.view',
            'cms.homepage.manage', 'cms.faq.manage', 'cms.pages.manage',
            'partners.manage', 'team.manage',
            'reviews.view', 'reviews.moderate', 'reviews.feature',
            'users.manage', 'clients.manage',
            'shop.products.manage', 'shop.categories.manage', 'shop.orders.view', 'shop.orders.update',
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
        ],

        'support' => [
            'client.view',
            'demande.view', 'demande.manage',
            'message.view',
            'reviews.view',
        ],

        'client' => [],
    ],
];
