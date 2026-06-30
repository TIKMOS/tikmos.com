<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function user(string $role, array $attributes = []): User
{
    $u = User::factory()->create($attributes);
    $u->assignRole($role);

    return $u;
}

it('redirige les invités vers la connexion sur /app', function (): void {
    $this->get('/app')->assertRedirect('/connexion');
});

it('redirige les invités vers la connexion sur /admin', function (): void {
    $this->get('/admin')->assertRedirect('/connexion');
});

it('autorise un client sur /app', function (): void {
    $this->actingAs(user(Role::Client->value))->get('/app')->assertOk();
});

it('interdit /admin à un client', function (): void {
    $this->actingAs(user(Role::Client->value))->get('/admin')->assertForbidden();
});

it('autorise un membre du staff sur /admin', function (): void {
    $this->actingAs(user(Role::Admin->value))->get('/admin')->assertOk();
});

it('déconnecte et bloque un compte suspendu', function (): void {
    $suspended = user(Role::Client->value, ['status' => 'suspended']);

    $this->actingAs($suspended)->get('/app')->assertRedirect('/connexion');
    $this->assertGuest();
});

it('vérifie les permissions des rôles', function (): void {
    expect(User::factory()->create()->assignRole(Role::SuperAdmin->value)->hasPermissionTo('roles.manage'))->toBeTrue();
    expect(user(Role::Admin->value)->hasPermissionTo('roles.manage'))->toBeFalse();
    expect(user(Role::Admin->value)->hasPermissionTo('colis.create'))->toBeTrue();
    expect(user(Role::AgentCotonou->value)->hasPermissionTo('vol.manage'))->toBeFalse();
});

it('autorise le commercial sur /admin et les demandes, mais pas la configuration', function (): void {
    $commercial = user(Role::Commercial->value);

    $this->actingAs($commercial)->get('/admin')->assertOk();
    $this->actingAs($commercial)->get('/admin/demandes')->assertOk();
    $this->actingAs($commercial)->get('/admin/reglages')->assertForbidden();
});

it('autorise l’éditeur sur le CMS, mais pas les demandes', function (): void {
    $editor = user(Role::Editor->value);

    $this->actingAs($editor)->get('/admin')->assertOk();
    $this->actingAs($editor)->get('/admin/reglages/accueil')->assertOk();
    $this->actingAs($editor)->get('/admin/demandes')->assertForbidden();
});
