<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Mode de paiement par défaut
    |--------------------------------------------------------------------------
    | 'cod'    → paiement à la livraison (interne)
    | 'online' → paiement en ligne (KKiaPay)
    | 'both'   → le client choisit au checkout
    |
    | Ce défaut est surchargé à l'exécution par PaymentSettings (espace admin).
    */
    'default_mode' => env('PAYMENTS_DEFAULT_MODE', 'cod'),

    /*
    |--------------------------------------------------------------------------
    | Devise
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYMENTS_CURRENCY', 'XOF'),

    /*
    |--------------------------------------------------------------------------
    | Passerelles
    |--------------------------------------------------------------------------
    */
    'gateways' => [

        'kkiapay' => [
            'public_key' => env('KKIAPAY_PUBLIC_KEY'),
            'private_key' => env('KKIAPAY_PRIVATE_KEY'),
            'secret' => env('KKIAPAY_SECRET'),
            'sandbox' => (bool) env('KKIAPAY_SANDBOX', true),
        ],

        'cod' => [
            //
        ],

    ],

];
