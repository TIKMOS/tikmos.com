<?php

use App\Enums\ParcelStatus;
use App\Enums\Role;
use App\Models\Parcel;
use App\Models\User;
use App\Notifications\ParcelReady;
use App\Notifications\ParcelStatusChanged;
use App\Services\ParcelService;
use App\Support\ReferenceGenerator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function newParcelAttributes(array $overrides = []): array
{
    return array_merge([
        'recipient_name' => 'Aïcha Koudous',
        'recipient_phone' => '+2290199000000',
        'type' => 'vetements',
        'origin' => 'cotonou',
        'destination' => 'paris',
        'weight_kg' => 3.5,
        'price' => 15000,
    ], $overrides);
}

it('génère des références incrémentales bien formées', function (): void {
    $a = ReferenceGenerator::next();
    Parcel::factory()->create(['reference' => $a]);
    $b = ReferenceGenerator::next();

    expect($a)->toMatch('/^SZ-\d{4}-\d{5}$/');
    expect($b)->not->toBe($a);
});

it('crée un colis avec un événement initial', function (): void {
    $parcel = app(ParcelService::class)->create(newParcelAttributes());

    expect($parcel->reference)->toMatch('/^SZ-\d{4}-\d{5}$/');
    expect($parcel->status)->toBe(ParcelStatus::Registered);
    expect($parcel->qr_token)->not->toBeNull();
    expect($parcel->events()->count())->toBe(1);
});

it('fait avancer le statut et notifie le client', function (): void {
    Notification::fake();
    $client = User::factory()->create();
    $parcel = app(ParcelService::class)->create(newParcelAttributes(['sender_id' => $client->id]));

    app(ParcelService::class)->advanceTo($parcel, ParcelStatus::InTransit);

    expect($parcel->fresh()->status)->toBe(ParcelStatus::InTransit);
    expect($parcel->events()->count())->toBe(2);
    Notification::assertSentTo($client, ParcelStatusChanged::class);
});

it('refuse une transition invalide', function (): void {
    $parcel = app(ParcelService::class)->create(newParcelAttributes());

    app(ParcelService::class)->advanceTo($parcel, ParcelStatus::Delivered);
})->throws(InvalidArgumentException::class);

it('envoie la notification « disponible » et marque la livraison', function (): void {
    Notification::fake();
    $client = User::factory()->create();
    $service = app(ParcelService::class);
    $parcel = $service->create(newParcelAttributes(['sender_id' => $client->id]));

    $service->advanceTo($parcel, ParcelStatus::InTransit);
    $service->advanceTo($parcel, ParcelStatus::Arrived);
    $service->advanceTo($parcel, ParcelStatus::ReadyForPickup);
    Notification::assertSentTo($client, ParcelReady::class);

    $service->advanceTo($parcel, ParcelStatus::Delivered);
    expect($parcel->fresh()->delivered_at)->not->toBeNull();
    expect($parcel->fresh()->status)->toBe(ParcelStatus::Delivered);
});

it('applique la policy : un client ne voit que ses colis', function (): void {
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);
    $own = Parcel::factory()->create(['sender_id' => $client->id]);
    $other = Parcel::factory()->create();

    expect($client->can('view', $own))->toBeTrue();
    expect($client->can('view', $other))->toBeFalse();
});
