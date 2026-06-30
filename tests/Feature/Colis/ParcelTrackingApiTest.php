<?php

use App\Models\Parcel;
use App\Models\ParcelEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('retourne la timeline publique d’un colis', function (): void {
    $parcel = Parcel::factory()->create([
        'reference' => 'SZ-2026-00042',
        'recipient_name' => 'Aïcha Koudous',
        'price' => 25000,
        'recipient_phone' => '+2290199000000',
    ]);
    ParcelEvent::factory()->for($parcel)->create(['status' => 'registered']);

    $response = $this->getJson('/api/track/SZ-2026-00042');

    $response->assertOk()
        ->assertJsonPath('data.reference', 'SZ-2026-00042')
        ->assertJsonPath('data.recipient', 'Aïcha K.')
        ->assertJsonStructure(['data' => ['reference', 'status' => ['value', 'label', 'tone'], 'events']]);
});

it('ne fuite aucune donnée sensible', function (): void {
    Parcel::factory()->create([
        'reference' => 'SZ-2026-00043',
        'price' => 25000,
        'recipient_phone' => '+2290188888888',
        'sender_phone' => '+33600000000',
    ]);

    $content = $this->getJson('/api/track/SZ-2026-00043')->getContent();

    expect($content)->not->toContain('25000');
    expect($content)->not->toContain('+33600000000');
    expect($content)->not->toContain('sender_phone');
});

it('renvoie 404 pour une référence inconnue', function (): void {
    $this->getJson('/api/track/SZ-2026-99999')->assertNotFound();
});
