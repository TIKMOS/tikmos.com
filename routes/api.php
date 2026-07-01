<?php

use Illuminate\Support\Facades\Route;

/*
| API « API-like » — voir docs/04-conventions-architecture.md §5.
| Préfixe /api (appliqué par bootstrap/app.php). Tout est throttlé.
| (Réservé pour les futurs endpoints publics ; ex. suivi de commande.)
*/

Route::middleware('throttle:60,1')->group(function (): void {
    //
});
