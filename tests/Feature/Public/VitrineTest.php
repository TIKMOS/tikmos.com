<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('rend les pages publiques', function (string $uri): void {
    $this->get($uri)->assertOk();
})->with([
    '/', '/services', '/realisations', '/blog', '/boutique', '/contact',
    '/a-propos', '/equipe', '/temoignages', '/faq', '/cgu', '/cgv', '/confidentialite', '/cookies',
]);

it('sert le sitemap en XML', function (): void {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset', false);
});

it('enregistre un message de contact', function (): void {
    Volt::test('pages.public.contact')
        ->set('name', 'Jean Client')
        ->set('email', 'jean@exemple.com')
        ->set('message', 'Bonjour, je voudrais un devis.')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leads', ['name' => 'Jean Client', 'email' => 'jean@exemple.com']);
});

it('exige au moins un téléphone ou un email au contact', function (): void {
    Volt::test('pages.public.contact')
        ->set('name', 'Jean')
        ->set('message', 'Bonjour')
        ->call('submit')
        ->assertHasErrors('phone');
});
