<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('charge toutes les pages admin GET sans 404/500 (super_admin)', function (): void {
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole(Role::SuperAdmin->value);

    $failures = [];
    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();
        if (! $name || ! str_starts_with($name, 'admin.')) {
            continue;
        }
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }
        // Ignore les routes à paramètres (testées séparément).
        if (str_contains($route->uri(), '{')) {
            continue;
        }

        $status = $this->actingAs($admin)->get('/'.ltrim($route->uri(), '/'))->status();
        if ($status !== 200) {
            $failures[] = $route->uri().' => '.$status;
        }
    }

    expect($failures)->toBe([]);
});
