<?php

use App\Enums\Role;
use App\Models\Partner;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function adminP(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('affiche les membres actifs sur /equipe', function (): void {
    TeamMember::create(['name' => 'Membre Visible 11', 'role_title' => 'Testeur', 'is_active' => true, 'position' => 1]);
    TeamMember::create(['name' => 'Membre Cache 12', 'role_title' => 'Fantôme', 'is_active' => false, 'position' => 2]);

    $this->get('/equipe')->assertOk()->assertSee('Membre Visible 11')->assertDontSee('Membre Cache 12');
});

it('affiche la zone partenaires actifs sur le site', function (): void {
    Partner::create(['name' => 'PartenaireActif 21', 'is_active' => true, 'position' => 1]);
    Partner::create(['name' => 'PartenaireMasque 22', 'is_active' => false, 'position' => 2]);

    $this->get('/')->assertOk()->assertSee('PartenaireActif 21')->assertDontSee('PartenaireMasque 22');
});

it('ouvre et gère l’admin partenaires', function (): void {
    $this->actingAs(adminP())->get('/admin/reglages/partenaires')->assertOk();

    Volt::actingAs(adminP())->test('pages.admin.reglages.partners')
        ->call('create')
        ->set('name', 'NouveauPartenaire 31')
        ->call('save')
        ->assertHasNoErrors();

    expect(Partner::where('name', 'NouveauPartenaire 31')->exists())->toBeTrue();
});

it('ouvre et gère l’admin équipe', function (): void {
    $this->actingAs(adminP())->get('/admin/reglages/equipe')->assertOk();

    Volt::actingAs(adminP())->test('pages.admin.reglages.team')
        ->call('create')
        ->set('name', 'NouveauMembre 41')
        ->set('role_title', 'Designer')
        ->call('save')
        ->assertHasNoErrors();

    expect(TeamMember::where('name', 'NouveauMembre 41')->exists())->toBeTrue();
});

it('refuse l’admin équipe sans permission', function (): void {
    $agent = User::factory()->create();
    $agent->assignRole(Role::Support->value);
    $this->actingAs($agent)->get('/admin/reglages/equipe')->assertForbidden();
});
