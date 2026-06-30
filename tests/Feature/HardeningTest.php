<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ajoute les en-têtes de sécurité', function (): void {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('fournit les vues d’erreur personnalisées', function (string $code): void {
    expect(view()->exists("errors.{$code}"))->toBeTrue();
})->with(['403', '404', '419', '429', '500', '503']);
