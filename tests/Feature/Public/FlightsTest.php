<?php

use App\Models\Flight;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('met en avant le vol featured comme prochain départ', function (): void {
    Flight::factory()->create(['departs_on' => now()->addDays(3), 'is_public' => true, 'is_featured' => false]);
    $featured = Flight::factory()->create(['departs_on' => now()->addDays(10), 'is_public' => true, 'is_featured' => true]);

    expect(Flight::nextDeparture()->id)->toBe($featured->id);
});

it('retombe sur le prochain vol à venir sans featured', function (): void {
    $soon = Flight::factory()->create(['departs_on' => now()->addDays(2), 'is_public' => true]);
    Flight::factory()->create(['departs_on' => now()->addDays(9), 'is_public' => true]);

    expect(Flight::nextDeparture()->id)->toBe($soon->id);
});

it('considère un vol clôturé comme tel', function (): void {
    $flight = Flight::factory()->create(['departs_on' => now()->addDays(5), 'cutoff_at' => now()->subDay(), 'is_public' => true]);

    expect($flight->isClosed())->toBeTrue();
});

it('affiche la carte prochain départ sur /vols', function (): void {
    Flight::factory()->create(['departs_on' => now()->addDays(4), 'is_public' => true]);

    $this->get('/vols')->assertOk()->assertSee('Prochain départ');
});
