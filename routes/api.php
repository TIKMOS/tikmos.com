<?php

use App\Http\Controllers\Api\TrackParcelController;
use Illuminate\Support\Facades\Route;

/*
| API « API-like » — voir docs/04-conventions-architecture.md §5.
| Préfixe /api (appliqué par bootstrap/app.php). Tout est throttlé.
*/

Route::middleware('throttle:60,1')->group(function (): void {
    // GET /api/track/{reference} — suivi public d'un colis
    Route::get('track/{parcel}', TrackParcelController::class)->name('api.track');
});
