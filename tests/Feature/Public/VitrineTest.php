<?php

use App\Models\Parcel;
use App\Models\ParcelEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('rend les pages publiques', function (string $uri): void {
    $this->get($uri)->assertOk();
})->with(['/', '/services', '/achat', '/vols', '/contact', '/suivi', '/a-propos', '/equipe', '/cgu', '/confidentialite', '/cookies']);

it('sert le sitemap en XML', function (): void {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset', false);
});

it('suit un colis existant via la page de suivi', function (): void {
    $parcel = Parcel::factory()->create(['reference' => 'SZ-2026-00777', 'status' => 'in_transit']);
    ParcelEvent::factory()->for($parcel)->create(['status' => 'registered']);
    ParcelEvent::factory()->for($parcel)->create(['status' => 'in_transit']);

    Volt::test('pages.public.track', ['reference' => 'SZ-2026-00777'])
        ->assertSee('SZ-2026-00777')
        ->assertSee('En transit');
});

it('affiche « introuvable » pour une référence inconnue', function (): void {
    Volt::test('pages.public.track')
        ->set('reference', 'SZ-2026-99999')
        ->call('search')
        ->assertSee('introuvable');
});

it('enregistre un message de contact', function (): void {
    Volt::test('pages.public.contact')
        ->set('name', 'Jean Diaspora')
        ->set('email', 'jean@exemple.com')
        ->set('message', 'Bonjour, je voudrais envoyer un colis.')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leads', ['name' => 'Jean Diaspora', 'email' => 'jean@exemple.com']);
});

it('exige au moins un téléphone ou un email au contact', function (): void {
    Volt::test('pages.public.contact')
        ->set('name', 'Jean')
        ->set('message', 'Bonjour')
        ->call('submit')
        ->assertHasErrors('phone');
});
