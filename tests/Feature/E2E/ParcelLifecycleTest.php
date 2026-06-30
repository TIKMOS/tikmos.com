<?php

use App\Enums\ParcelStatus;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\ParcelReady;
use App\Notifications\ParcelStatusChanged;
use App\Services\ParcelService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('scénario complet : création → livraison → suivi client & public', function (): void {
    Notification::fake();

    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);
    $service = app(ParcelService::class);

    // 1. L'équipe crée un colis pour le client.
    $parcel = $service->create([
        'sender_id' => $client->id, 'recipient_name' => 'Marc Dossou', 'type' => 'vetements',
        'origin' => 'cotonou', 'destination' => 'paris', 'weight_kg' => 4, 'price' => 18000,
    ]);
    expect($parcel->status)->toBe(ParcelStatus::Registered);
    expect($parcel->events()->count())->toBe(1);

    // 2. Le colis parcourt tout le cycle (transitions contrôlées + notifications).
    foreach ([ParcelStatus::InTransit, ParcelStatus::Arrived, ParcelStatus::ReadyForPickup, ParcelStatus::Delivered] as $status) {
        $service->advanceTo($parcel, $status, location: 'SEZY');
    }
    expect($parcel->fresh()->status)->toBe(ParcelStatus::Delivered);
    expect($parcel->fresh()->delivered_at)->not->toBeNull();
    Notification::assertSentTo($client, ParcelReady::class);
    Notification::assertSentTo($client, ParcelStatusChanged::class);

    // 3. Le client retrouve et consulte son colis.
    $this->actingAs($client);
    Volt::test('pages.app.colis-index')->assertSee($parcel->reference);
    $this->get(route('app.colis.show', $parcel->reference))->assertOk()->assertSee('Livré');

    // 4. Le suivi public (API + page) reflète l'état final.
    $this->get('/api/track/'.$parcel->reference)
        ->assertOk()
        ->assertJsonPath('data.status.value', 'delivered');

    // La page publique affiche le statut mais jamais le nom du destinataire (vie privée).
    Volt::test('pages.public.track', ['reference' => $parcel->reference])
        ->assertSee('Livré')
        ->assertSee($parcel->reference)
        ->assertDontSee('Marc');
});
