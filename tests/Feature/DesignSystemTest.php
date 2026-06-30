<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * La galerie du design system (`/_design`) n'est routée qu'en environnement `local`.
 * En test (`testing`), on enregistre une route équivalente sous le middleware `web`
 * pour valider que la vue compile et rend de bout en bout (composants `x-tk.*`, layout).
 */
it('rend la galerie du design system (composants x-tk) sans erreur', function (): void {
    Route::view('/_design_smoke', 'design-demo')->middleware('web');

    $response = $this->get('/_design_smoke');

    $response->assertOk();
    // La charte est bien appliquée (rayon de bouton + accent or présents)
    $response->assertSee('rounded-btn', false);
    $response->assertSee('gold', false);
    // Plus aucune balise de composant héritée x-sz
    $response->assertDontSee('x-sz', false);
});
