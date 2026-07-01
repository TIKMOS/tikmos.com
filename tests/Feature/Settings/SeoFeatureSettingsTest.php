<?php

declare(strict_types=1);

use App\Settings\FeatureSettings;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('charge les réglages SEO seedés (Person/Organization)', function (): void {
    $seo = app(SeoSettings::class);

    expect($seo->organization_name)->toBe('Tikmos')
        ->and($seo->person_name)->not->toBe('')
        ->and($seo->default_title)->toContain('Tikmos')
        ->and($seo->person_job_title)->toContain('IA');
});

it('charge les feature flags seedés', function (): void {
    $f = app(FeatureSettings::class);

    expect($f->blog)->toBeTrue()
        ->and($f->pwa)->toBeFalse()
        ->and($f->online_payment)->toBeFalse();
});

it('persiste une modification de feature flag', function (): void {
    $f = app(FeatureSettings::class);
    $f->pwa = true;
    $f->save();

    expect(app(FeatureSettings::class)->pwa)->toBeTrue();
});

it('pilote le numéro WhatsApp depuis les réglages (source unique)', function (): void {
    $g = app(GeneralSettings::class);

    // La valeur a été reciblée vers un placeholder Tikmos (plus le numéro Sezy).
    expect($g->whatsapp_number)->toBe('2290197440615')
        ->and($g->emails['contact'])->toBe('contact@tikmos.com');
});
