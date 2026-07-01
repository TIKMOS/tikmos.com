<?php

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Settings\HomepageSettings;
use App\Settings\ReviewsSettings;
use App\Settings\ShopSettings;

it('charge les réglages généraux avec les valeurs migrées', function (): void {
    $g = app(GeneralSettings::class);

    expect($g->brand_phone)->toBe('+229 01 97 44 06 15')
        ->and($g->delivered_base)->toBe(1200)
        ->and($g->nav_links)->toHaveCount(3)
        ->and($g->top_bandeau_enabled)->toBeTrue();
});

it('charge les réglages accueil', function (): void {
    $h = app(HomepageSettings::class);

    expect($h->hero_title)->toContain('livre')
        ->and($h->services)->toHaveCount(4)
        ->and($h->sections_order)->toContain('projects')
        ->and($h->sections_order)->toContain('team');
});

it('charge les réglages avis et boutique', function (): void {
    // La boutique est activée par le seed démo (migration). La devise reste XOF.
    expect(app(ReviewsSettings::class)->delay_days)->toBe(7)
        ->and(app(ShopSettings::class)->enabled)->toBeTrue()
        ->and(app(ShopSettings::class)->currency)->toBe('XOF');
});

it('persiste une modification de réglage', function (): void {
    $g = app(GeneralSettings::class);
    $g->delivered_base = 1500;
    $g->save();

    expect(app(GeneralSettings::class)->delivered_base)->toBe(1500);
});
