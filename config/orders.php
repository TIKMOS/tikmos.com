<?php

declare(strict_types=1);

return [
    // Initialisation de migration uniquement : plafonds historiques vérifiés, jamais décrémentés.
    'number_sequence_floors' => env('ORDER_NUMBER_SEQUENCE_FLOORS'),
];
