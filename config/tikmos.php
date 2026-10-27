<?php

return [
    // Coordonnées — TODO: remplacer par les vraies infos Tikmos (surchargées par les réglages admin).
    'whatsapp' => env('TIKMOS_WHATSAPP', '2290000000000'),
    'phones' => [
        '+229 00 00 00 00 00',
    ],
    'addresses' => [
        'principal' => 'Adresse Tikmos — à compléter',
    ],

    // Statistiques vitrine (placeholder avant données réelles).
    'stats' => [
        'delivered_base' => (int) env('TIKMOS_PROJECTS_BASE', 0),
    ],

    // Pays affichés dans les champs téléphone (gérable depuis l'admin).
    'default_country' => 'bj',
    'countries' => [
        ['code' => 'bj', 'name' => 'Bénin', 'dial' => '+229'],
        ['code' => 'fr', 'name' => 'France', 'dial' => '+33'],
        ['code' => 'be', 'name' => 'Belgique', 'dial' => '+32'],
        ['code' => 'ci', 'name' => "Côte d'Ivoire", 'dial' => '+225'],
        ['code' => 'tg', 'name' => 'Togo', 'dial' => '+228'],
        ['code' => 'sn', 'name' => 'Sénégal', 'dial' => '+221'],
        ['code' => 'ng', 'name' => 'Nigéria', 'dial' => '+234'],
        ['code' => 'ca', 'name' => 'Canada', 'dial' => '+1'],
        ['code' => 'us', 'name' => 'États-Unis', 'dial' => '+1'],
        ['code' => 'gb', 'name' => 'Royaume-Uni', 'dial' => '+44'],
    ],

    // Double authentification (OTP email) pour les rôles de gestion.
    'two_factor' => [
        'enabled' => (bool) env('TIKMOS_2FA_ENABLED', true),
        'ttl' => 600,          // durée de validité du code (secondes)
        'max_attempts' => 3,   // codes incorrects par fenêtre, tous challenges confondus
        'attempt_window' => 900,
        'resend_delay' => 60,
        'max_sends' => 5,      // premier envoi inclus, par compte
        'send_window' => 900,
        'remember_days' => 30, // durée de confiance d'un appareil
        // Mode développement : affiche le code OTP à l'écran. N'a d'effet QUE si APP_ENV=local.
        'dev_show_code' => (bool) env('TIKMOS_DEV_SHOW_CODE', true),
    ],
];
