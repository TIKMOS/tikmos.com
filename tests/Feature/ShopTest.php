<?php

use App\Enums\Role;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Settings\ShopSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function shopAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('affiche la boutique et un produit quand elle est activée', function (): void {
    // La migration de seed démo a activé la boutique + des produits.
    $product = Product::active()->first();

    $this->get('/boutique')->assertOk()->assertSee($product->name);
    $this->get('/boutique/'.$product->slug)->assertOk()->assertSee($product->priceLabel());
});

it('renvoie 404 pour un produit inactif', function (): void {
    Product::create(['name' => 'Inactif 9', 'slug' => 'inactif-9', 'price' => 1000, 'is_active' => false]);

    $this->get('/boutique/inactif-9')->assertNotFound();
});

it('renvoie 404 sur la boutique désactivée', function (): void {
    $s = app(ShopSettings::class);
    $s->enabled = false;
    $s->save();

    $this->get('/boutique')->assertNotFound();
});

it('crée un produit depuis l’admin', function (): void {
    $cat = ProductCategory::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);

    Volt::actingAs(shopAdmin())->test('pages.admin.boutique.products')
        ->call('create')
        ->set('name', 'Produit Test 77')
        ->set('category_id', $cat->id)
        ->set('price', 5000)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Produit Test 77')->first();
    expect($product)->not->toBeNull()
        ->and($product->slug)->toBe('produit-test-77')
        ->and($product->price)->toBe(5000);
});

it('refuse l’admin boutique sans permission', function (): void {
    $agent = User::factory()->create();
    $agent->assignRole(Role::Support->value);

    $this->actingAs($agent)->get('/admin/boutique')->assertForbidden();
});
