<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function staffWith(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('affiche le hub Réglages pour un rôle avec settings.manage', function (): void {
    $this->actingAs(staffWith(Role::Admin->value))
        ->get('/admin/reglages')
        ->assertOk()
        ->assertSee('Réglages')
        ->assertSee('Général')
        ->assertSee('Transporteurs');
});

it('refuse le hub Réglages sans settings.manage', function (): void {
    // agent_cotonou est un rôle de gestion mais n'a pas settings.manage
    $this->actingAs(staffWith(Role::AgentCotonou->value))
        ->get('/admin/reglages')
        ->assertForbidden();
});

it('refuse le hub Réglages à un client', function (): void {
    $this->actingAs(staffWith(Role::Client->value))
        ->get('/admin/reglages')
        ->assertForbidden();
});
