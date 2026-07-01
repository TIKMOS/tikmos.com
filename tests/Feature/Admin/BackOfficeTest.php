<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    // Le support n'a pas services.manage ni settings.manage.
    $support = staff(Role::Support->value);
    $this->actingAs($support)->get('/admin/services')->assertForbidden();
    $this->actingAs($support)->get('/admin/reglages')->assertForbidden();

    // L'admin a tout.
    $admin = staff(Role::Admin->value);
    $this->actingAs($admin)->get('/admin/services')->assertOk();
    $this->actingAs($admin)->get('/admin/clients')->assertOk();
});
