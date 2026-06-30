<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expose un manifest PWA valide', function (): void {
    $path = public_path('manifest.webmanifest');
    expect(file_exists($path))->toBeTrue();

    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) file_get_contents($path), true);

    expect($manifest)->toHaveKeys(['name', 'short_name', 'start_url', 'scope', 'display', 'icons']);
    expect($manifest['display'])->toBe('standalone');
    expect($manifest['icons'])->toHaveCount(3);
});

it('fournit les fichiers PWA et icônes', function (string $file): void {
    expect(file_exists(public_path($file)))->toBeTrue();
})->with([
    'sw.js',
    'offline.html',
    'icons/icon-192.png',
    'icons/icon-512.png',
    'icons/maskable-512.png',
    'icons/apple-touch-icon.png',
]);

it('référence le manifest et apple-touch dans les pages', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('apple-touch-icon', false)
        ->assertSee('apple-mobile-web-app-capable', false);
});
