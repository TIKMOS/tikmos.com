<?php

use App\Enums\Role;
use App\Models\PreferredSite;
use App\Models\User;
use App\Settings\GeneralSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function admin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('ouvre le réglage Général pour un admin', function (): void {
    $this->actingAs(admin())->get('/admin/reglages/general')->assertOk()->assertSee('Contacts');
});

it('refuse le réglage Général sans permission', function (): void {
    $u = User::factory()->create();
    $u->assignRole(Role::AgentCotonou->value);
    $this->actingAs($u)->get('/admin/reglages/general')->assertForbidden();
});

it('enregistre les réglages généraux', function (): void {
    Volt::actingAs(admin())->test('pages.admin.reglages.general')
        ->set('brand_phone', '+229 90 00 00 00')
        ->set('footer_tagline', 'Le pont de confiance.')
        ->set('delivered_base', 1500)
        ->call('save')
        ->assertHasNoErrors();

    $s = app(GeneralSettings::class);
    expect($s->brand_phone)->toBe('+229 90 00 00 00')
        ->and($s->delivered_base)->toBe(1500);
});

it('reflète les réglages sur le site public', function (): void {
    $s = app(GeneralSettings::class);
    $s->footer_tagline = 'Slogan de test unique 4242';
    $s->save();

    $this->get('/')->assertOk()->assertSee('Slogan de test unique 4242');
});

it('gère les sites préférés et les affiche sur Achat', function (): void {
    $this->actingAs(admin())->get('/admin/reglages/sites')->assertOk();

    Volt::actingAs(admin())->test('pages.admin.reglages.sites')
        ->call('create')
        ->set('name', 'BoutiqueDemo42')
        ->call('save')
        ->assertHasNoErrors();

    expect(PreferredSite::where('name', 'BoutiqueDemo42')->exists())->toBeTrue();

    $this->get('/achat')->assertOk()->assertSee('BoutiqueDemo42');
});

it('masque un site préféré inactif sur Achat', function (): void {
    PreferredSite::create(['name' => 'SiteMasque99', 'is_active' => false, 'position' => 1]);

    $this->get('/achat')->assertOk()->assertDontSee('SiteMasque99');
});
