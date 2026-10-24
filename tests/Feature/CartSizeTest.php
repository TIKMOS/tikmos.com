<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Http::preventStrayRequests();
    Notification::fake();
});

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

it('refuse un ajout dont le cumul des variantes dépasse le stock global', function (): void {
    $product = Product::create(['name' => 'Article partagé', 'slug' => 'stock-partage', 'price' => 1000, 'stock' => 2, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 1, '41');
    $cart->add($product->id, 1, '42');
    $before = session('cart');

    expect(fn () => $cart->add($product->id, 1, '42'))->toThrow(ValidationException::class)
        ->and(session('cart'))->toBe($before)
        ->and($cart->count())->toBe(2)
        ->and($product->fresh()->stock)->toBe(2);
});

it('affiche le stock global au refus depuis la fiche produit', function (): void {
    $product = Product::create(['name' => 'Article partagé', 'slug' => 'stock-fiche', 'price' => 1000, 'stock' => 2, 'sizes' => [41, 42], 'is_active' => true]);
    $component = Volt::test('pages.public.shop-show', ['product' => $product]);
    $component->call('addToCart', '41')->call('addToCart', '42');
    $component->call('addToCart', '42')
        ->assertHasErrors('cart')
        ->assertSee('2 unité(s) disponible(s) au total')
        ->assertSee('Modifier mon panier')
        ->assertNotDispatched('cart-updated')
        ->assertNotDispatched('tk-toast');

    expect(app(CartService::class)->count())->toBe(2);
});

it('refuse une augmentation excessive dans le panier sans changer les quantités', function (): void {
    $product = Product::create(['name' => 'Article partagé', 'slug' => 'stock-quantites', 'price' => 1000, 'stock' => 3, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 1, '41');
    $cart->add($product->id, 1, '42');
    $before = session('cart');
    $component = Volt::test('pages.public.cart');
    $component->call('updateQty', $product->id.':41', 3)
        ->assertHasErrors('cart')
        ->assertSee('3 unité(s) disponible(s) au total')
        ->assertNotDispatched('cart-updated');
    expect(session('cart'))->toBe($before);

    $component->call('updateQty', $product->id.':41', 2)->assertHasNoErrors('cart');
    expect($cart->count())->toBe(3);
});

it('conserve les quantités demandées après une baisse de stock et autorise leur correction progressive', function (): void {
    $product = Product::create(['name' => 'Article partagé', 'slug' => 'stock-baisse', 'price' => 1000, 'stock' => 4, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 2, '41');
    $cart->add($product->id, 2, '42');
    $product->update(['stock' => 2]);

    expect($cart->count())->toBe(4);
    $cart->update($product->id.':41', 1);
    expect($cart->count())->toBe(3);
    $cart->update($product->id.':42', 1);
    expect($cart->count())->toBe(2)
        ->and($product->fresh()->stock)->toBe(2);
});

it('revérifie le stock actuel à chaque ajout', function (): void {
    $product = Product::create(['name' => 'Article partagé', 'slug' => 'stock-ajout', 'price' => 1000, 'stock' => 2, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 1, '41');
    $product->update(['stock' => 1]);

    expect(fn () => $cart->add($product->id, 1, '42'))->toThrow(ValidationException::class)
        ->and($cart->count())->toBe(1);
});

it('préserve les stocks non gérés et la limite par ligne', function (): void {
    $product = Product::create(['name' => 'Article sans stock', 'slug' => 'stock-illimite', 'price' => 1000, 'stock' => null, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 20, '41');
    $cart->add($product->id, 20, '42');
    $cart->update($product->id.':41', 21);

    expect($cart->items())->toHaveCount(2)
        ->and($cart->count())->toBe(40)
        ->and($product->fresh()->stock)->toBeNull();
});

it('compte séparément les stocks de deux produits', function (): void {
    $first = Product::create(['name' => 'Premier', 'slug' => 'stock-premier', 'price' => 1000, 'stock' => 1, 'sizes' => [41], 'is_active' => true]);
    $second = Product::create(['name' => 'Second', 'slug' => 'stock-second', 'price' => 1000, 'stock' => 1, 'sizes' => [41], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($first->id, 1, '41');
    $cart->add($second->id, 1, '41');

    expect($cart->count())->toBe(2);
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
