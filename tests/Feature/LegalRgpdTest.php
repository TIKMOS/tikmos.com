<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function rgpdClient(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('affiche les pages légales Tikmos (sans mention Sezy)', function (): void {
    $this->get('/cgu')->assertOk()->assertSee("Conditions générales d'utilisation")->assertDontSee('SEZY', false);
    $this->get('/confidentialite')->assertOk()->assertSee('Confidentialité')->assertDontSee('SEZY', false);
});

it('expose les CGV de la boutique (paiement à la livraison)', function (): void {
    $this->get('/cgv')
        ->assertOk()
        ->assertSee('Conditions générales de vente')
        ->assertSee('paiement s\'effectue à la livraison', false);
});

it('permet au client d’exporter ses données (RGPD)', function (): void {
    Volt::actingAs(rgpdClient())->test('pages.app.compte')
        ->call('exportData')
        ->assertFileDownloaded('mes-donnees-tikmos.json');
});

it('permet au client de supprimer son compte (droit à l’effacement)', function (): void {
    $client = rgpdClient();

    Volt::actingAs($client)->test('pages.app.compte')->call('deleteAccount');

    expect(User::find($client->id))->toBeNull();
});
