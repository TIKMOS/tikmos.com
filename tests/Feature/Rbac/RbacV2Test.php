<?php

use App\Enums\Role as RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('crée les rôles Tikmos', function (): void {
    expect(Role::where('name', 'gestionnaire_boutique')->exists())->toBeTrue()
        ->and(Role::where('name', 'commercial')->exists())->toBeTrue()
        ->and(Role::where('name', 'editor')->exists())->toBeTrue();
});

it('donne au gestionnaire boutique les droits commerce', function (): void {
    $gb = Role::findByName('gestionnaire_boutique', 'web');

    expect($gb->hasPermissionTo('shop.products.manage'))->toBeTrue()
        ->and($gb->hasPermissionTo('shop.orders.update'))->toBeTrue()
        ->and($gb->hasPermissionTo('reviews.moderate'))->toBeTrue()
        ->and($gb->hasPermissionTo('users.manage'))->toBeFalse();
});

it('donne tout au super admin', function (): void {
    $sa = Role::findByName('super_admin', 'web');

    expect($sa->permissions()->count())->toBe(Permission::count());
});

it('expose les nouveaux rôles dans l’enum', function (): void {
    expect(RoleEnum::cases())->toHaveCount(7)
        ->and(RoleEnum::management())->toContain(RoleEnum::GestionnaireBoutique)
        ->and(RoleEnum::management())->toContain(RoleEnum::Commercial)
        ->and(RoleEnum::management())->toContain(RoleEnum::Editor)
        ->and(RoleEnum::management())->not->toContain(RoleEnum::Client)
        ->and(RoleEnum::GestionnaireBoutique->label())->toBe('Gestionnaire boutique');
});

it('donne au commercial les droits devis/demandes sans config ni équipe', function (): void {
    $c = Role::findByName('commercial', 'web');

    expect($c->hasPermissionTo('demande.manage'))->toBeTrue()
        ->and($c->hasPermissionTo('quotes.send'))->toBeTrue()
        ->and($c->hasPermissionTo('client.manage'))->toBeTrue()
        ->and($c->hasPermissionTo('settings.manage'))->toBeFalse()
        ->and($c->hasPermissionTo('users.manage'))->toBeFalse()
        ->and($c->hasPermissionTo('shop.products.manage'))->toBeFalse();
});

it('cloisonne l’éditeur au contenu (CMS) sans accès financier ni clients', function (): void {
    $e = Role::findByName('editor', 'web');

    expect($e->hasPermissionTo('cms.pages.manage'))->toBeTrue()
        ->and($e->hasPermissionTo('services.manage'))->toBeTrue()
        ->and($e->hasPermissionTo('blog.manage'))->toBeTrue()
        ->and($e->hasPermissionTo('client.manage'))->toBeFalse()
        ->and($e->hasPermissionTo('demande.manage'))->toBeFalse()
        ->and($e->hasPermissionTo('settings.manage'))->toBeFalse();
});
