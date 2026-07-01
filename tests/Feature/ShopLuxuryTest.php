<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function luxAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::GestionnaireBoutique->value);

    return $u;
}

it('présente la boutique (équipements & accessoires de luxe + marque)', function (): void {
    $cat = ProductCategory::create(['name' => 'Cat Hommes Test', 'slug' => 'cat-hommes-test', 'is_active' => true]);
    Product::create([
        'category_id' => $cat->id, 'name' => 'Laptop test', 'brand' => 'Maison Tikmos',
        'slug' => 'laptop-test', 'price' => 250000, 'is_active' => true,
    ]);

    $this->get('/boutique')
        ->assertOk()
        ->assertSee('accessoires de luxe')
        ->assertSee('Laptop test')
        ->assertSee('Maison Tikmos');
});

it('affiche la marque et le sélecteur de tailles sur la fiche', function (): void {
    Product::create([
        'name' => 'Sneaker test', 'brand' => 'Atelier Lux', 'slug' => 'sneaker-test',
        'price' => 195000, 'sizes' => [40, 41, 42], 'is_active' => true,
    ]);

    $this->get('/boutique/sneaker-test')
        ->assertOk()
        ->assertSee('Atelier Lux')
        ->assertSee('Taille')
        ->assertSee('41');
});

it('stocke marque et tailles depuis l’admin', function (): void {
    $cat = ProductCategory::create(['name' => 'Cat Femmes Test', 'slug' => 'cat-femmes-test', 'is_active' => true]);

    Volt::actingAs(luxAdmin())->test('pages.admin.boutique.products')
        ->call('create')
        ->set('name', 'Ballerine premium')
        ->set('brand', 'Maison Tikmos')
        ->set('sizes', '36, 37, 38')
        ->set('category_id', $cat->id)
        ->set('price', 165000)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstWhere('name', 'Ballerine premium');
    expect($product->brand)->toBe('Maison Tikmos')
        ->and($product->sizeList())->toBe(['36', '37', '38'])
        ->and($product->hasSizes())->toBeTrue();
});

it('gère un produit sans tailles', function (): void {
    $product = Product::create(['name' => 'Sans taille', 'slug' => 'sans-taille', 'price' => 1000, 'is_active' => true]);

    expect($product->hasSizes())->toBeFalse()
        ->and($product->sizeList())->toBe([]);
});
