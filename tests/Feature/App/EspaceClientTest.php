<?php

use App\Enums\Role;
use App\Models\Parcel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->client = User::factory()->create();
    $this->client->assignRole(Role::Client->value);
});

it('affiche le tableau de bord client', function (): void {
    $this->actingAs($this->client)->get('/app')->assertOk();
});

it('liste uniquement les colis du client', function (): void {
    Parcel::factory()->create(['sender_id' => $this->client->id, 'reference' => 'SZ-2026-10001']);
    Parcel::factory()->create(['reference' => 'SZ-2026-10002']);

    $this->actingAs($this->client);

    Volt::test('pages.app.colis-index')
        ->assertSee('SZ-2026-10001')
        ->assertDontSee('SZ-2026-10002');
});

it('autorise le détail de son colis et interdit celui des autres', function (): void {
    $own = Parcel::factory()->create(['sender_id' => $this->client->id]);
    $other = Parcel::factory()->create();

    $this->actingAs($this->client)->get(route('app.colis.show', $own->reference))->assertOk();
    $this->actingAs($this->client)->get(route('app.colis.show', $other->reference))->assertForbidden();
});

it('crée une demande d’expédition', function (): void {
    $this->actingAs($this->client);

    Volt::test('pages.app.demande-create')
        ->set('type', 'vetements')
        ->set('origin', 'cotonou')
        ->set('destination', 'paris')
        ->set('recipient_name', 'Marc Dossou')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.dashboard'));

    $this->assertDatabaseHas('parcel_requests', ['user_id' => $this->client->id, 'status' => 'new']);
});

it('refuse une demande origine = destination', function (): void {
    $this->actingAs($this->client);

    Volt::test('pages.app.demande-create')
        ->set('origin', 'paris')
        ->set('destination', 'paris')
        ->call('submit')
        ->assertHasErrors('destination');
});

it('met à jour le profil', function (): void {
    $this->actingAs($this->client);

    Volt::test('pages.app.compte')
        ->set('name', 'Nouveau Nom')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->client->fresh()->name)->toBe('Nouveau Nom');
});

it('supprime le compte (RGPD)', function (): void {
    $this->actingAs($this->client);

    Volt::test('pages.app.compte')
        ->call('deleteAccount')
        ->assertRedirect('/');

    expect(User::withTrashed()->find($this->client->id)->trashed())->toBeTrue();
});
