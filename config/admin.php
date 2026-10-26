<?php

declare(strict_types=1);

return [
    // Provisionnement explicite par AdminSeeder uniquement, jamais par DatabaseSeeder.
    'name' => env('TIKMOS_ADMIN_NAME'),
    'email' => env('TIKMOS_ADMIN_EMAIL'),
    'password' => env('TIKMOS_ADMIN_PASSWORD'),
];
