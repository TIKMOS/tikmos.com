<?php

use App\Enums\ParcelStatus;
use App\Enums\Role;
use App\Models\Parcel;
use App\Models\ParcelRequest;
use App\Models\User;
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

function staff(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('affiche le dashboard admin pour le staff', function (): void {
    $this->actingAs(staff(Role::Admin->value))->get('/admin')->assertOk();
});

it('applique les permissions fines par section', function (): void {
    // agent_cotonou a colis.view mais pas vol.view
    $agent = staff(Role::AgentCotonou->value);
    $this->actingAs($agent)->get('/admin/colis')->assertOk();
    $this->actingAs($agent)->get('/admin/vols')->assertForbidden();

    // admin a tout
    $admin = staff(Role::Admin->value);
    $this->actingAs($admin)->get('/admin/vols')->assertOk();
    $this->actingAs($admin)->get('/admin/clients')->assertOk();
});

it('fait avancer le statut d’un colis et notifie le client', function (): void {
    Notification::fake();
    $client = User::factory()->create();
    $parcel = app(ParcelService::class)->create([
        'sender_id' => $client->id, 'recipient_name' => 'X', 'type' => 'vetements',
        'origin' => 'cotonou', 'destination' => 'paris',
    ]);

    $this->actingAs(staff(Role::Admin->value));

    Volt::test('pages.admin.colis-show', ['reference' => $parcel->reference])
        ->set('newStatus', 'in_transit')
        ->call('advance')
        ->assertHasNoErrors();

    expect($parcel->fresh()->status)->toBe(ParcelStatus::InTransit);
    Notification::assertSentTo($client, ParcelStatusChanged::class);
});

it('refuse une transition de statut invalide', function (): void {
    $parcel = app(ParcelService::class)->create([
        'recipient_name' => 'X', 'type' => 'vetements', 'origin' => 'cotonou', 'destination' => 'paris',
    ]);

    $this->actingAs(staff(Role::Admin->value));

    Volt::test('pages.admin.colis-show', ['reference' => $parcel->reference])
        ->set('newStatus', 'delivered')
        ->call('advance')
        ->assertHasErrors('newStatus');
});

it('crée un vol', function (): void {
    $this->actingAs(staff(Role::Admin->value));

    Volt::test('pages.admin.vols')
        ->call('newFlight')
        ->set('direction', 'paris_cotonou')
        ->set('departs_on', now()->addWeek()->toDateString())
        ->set('status', 'scheduled')
        ->set('is_public', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('flights', ['direction' => 'paris_cotonou', 'is_public' => true]);
});

it('convertit une demande en colis', function (): void {
    $client = User::factory()->create();
    $request = ParcelRequest::factory()->create(['user_id' => $client->id, 'status' => 'new']);

    $this->actingAs(staff(Role::Admin->value));

    Volt::test('pages.admin.demandes')
        ->call('convert', $request->id)
        ->assertHasNoErrors();

    expect($request->fresh()->status->value)->toBe('converted');
    expect(Parcel::where('sender_id', $client->id)->exists())->toBeTrue();
});
