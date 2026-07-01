<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function sizeBuyer(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('sépare les lignes de panier par taille', function (): void {
    $p = Product::create(['name' => 'Sneaker', 'slug' => 'sneaker-x', 'price' => 100000, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($p->id, 1, '41');
    $cart->add($p->id, 1, '42');
    $cart->add($p->id, 1, '41'); // même taille → incrémente la ligne existante

    $items = $cart->items();
    expect($items)->toHaveCount(2)
        ->and($cart->count())->toBe(3);
});

it('exige une taille valide pour ajouter un produit à pointures', function (): void {
    $p = Product::create(['name' => 'Derby', 'slug' => 'derby-x', 'price' => 200000, 'sizes' => [40, 41], 'is_active' => true]);

    // Sans taille → rien ajouté.
    Volt::test('pages.public.shop-show', ['product' => $p])->call('addToCart', null);
    expect(app(CartService::class)->isEmpty())->toBeTrue();

    // Taille invalide → rien ajouté.
    Volt::test('pages.public.shop-show', ['product' => $p])->call('addToCart', '99');
    expect(app(CartService::class)->isEmpty())->toBeTrue();

    // Taille valide → ajouté.
    Volt::test('pages.public.shop-show', ['product' => $p])->call('addToCart', '41');
    expect(app(CartService::class)->count())->toBe(1);
});

it('enregistre la taille sur la ligne de commande au checkout', function (): void {
    $user = sizeBuyer();
    $p = Product::create(['name' => 'Escarpin', 'slug' => 'escarpin-x', 'price' => 150000, 'stock' => 5, 'sizes' => [37, 38], 'is_active' => true]);
    app(CartService::class)->add($p->id, 1, '38');

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Cliente Lux')
        ->set('contact_phone', '+229 91000000')
        ->set('delivery_address', 'Rue 12')
        ->set('delivery_city', 'Cotonou')
        ->call('place');

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->items->first()->size)->toBe('38');
});

it('propose une commande WhatsApp pré-remplie depuis le panier', function (): void {
    $p = Product::create(['name' => 'Mocassin', 'slug' => 'mocassin-x', 'price' => 240000, 'sizes' => [42], 'is_active' => true]);
    app(CartService::class)->add($p->id, 1, '42');

    $this->get('/panier')
        ->assertOk()
        ->assertSee('Commander sur WhatsApp')
        ->assertSee('wa.me', false)
        ->assertSee('Taille : ', false);
});
