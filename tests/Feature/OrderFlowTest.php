<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

function buyer(): User
{
    $u = User::factory()->create(['status' => 'active', 'phone' => '+229 90000000', 'city' => 'Cotonou']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('calcule le panier côté serveur (prix depuis la base)', function (): void {
    $p = Product::create(['name' => 'Item A', 'slug' => 'item-a', 'price' => 5000, 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($p->id, 3);

    expect($cart->count())->toBe(3)
        ->and($cart->subtotal())->toBe(15000);
});

it('purge un produit inactif du panier', function (): void {
    $p = Product::create(['name' => 'Item B', 'slug' => 'item-b', 'price' => 5000, 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($p->id, 2);
    $p->update(['is_active' => false]);

    expect($cart->isEmpty())->toBeTrue();
});

it('crée une commande COD au checkout et vide le panier', function (): void {
    $user = buyer();
    $p = Product::create(['name' => 'Item C', 'slug' => 'item-c', 'price' => 8000, 'stock' => 5, 'is_active' => true]);
    app(CartService::class)->add($p->id, 2);

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Jean Acheteur')
        ->set('contact_phone', '+229 91000000')
        ->set('delivery_address', 'Quartier Jak, rue 12')
        ->set('delivery_city', 'Cotonou')
        ->call('place');

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->total)->toBe(16000)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($order->payment_method->value)->toBe('cod')
        ->and(app(CartService::class)->isEmpty())->toBeTrue()
        ->and($p->fresh()->stock)->toBe(3);
});

it('refuse un panier historique de quatre unités sur deux variantes pour un stock global de deux', function (): void {
    $product = Product::create([
        'name' => 'Produit stock global', 'slug' => 'stock-global', 'price' => 1000,
        'stock' => 2, 'sizes' => [41, 42], 'is_active' => true,
    ]);
    // Panier possible avant F03, conservé en session lors du déploiement de la correction.
    $basket = [$product->id.':41' => 2, $product->id.':42' => 2];
    session()->put('cart', $basket);
    $refused = false;

    try {
        app(OrderService::class)->place(buyer(), [
            'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
        ]);
    } catch (ValidationException $exception) {
        $refused = true;
        expect($exception->errors())->toHaveKey('cart');
    }

    expect($product->fresh()->stock)->toBe(2)
        ->and($refused)->toBeTrue()
        ->and(Order::count())->toBe(0)
        ->and(session('cart'))->toBe($basket);
    Notification::assertNothingSent();
});

it('refuse une baisse de stock après ouverture du checkout et conserve un panier corrigeable', function (int $remaining): void {
    $user = buyer();
    $product = Product::create(['name' => 'Article stock', 'slug' => 'stock-checkout', 'price' => 1000, 'stock' => 2, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 1, '41');
    $cart->add($product->id, 1, '42');
    $before = session('cart');
    $component = Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Audit')
        ->set('contact_phone', '+2290190000000')
        ->set('delivery_address', 'Test uniquement');
    $product->update(['stock' => $remaining]);

    $component->call('place')->assertHasErrors('cart')
        ->assertSee($remaining.' unité(s) disponible(s) au total')
        ->assertSee('Modifier mon panier')
        ->assertNoRedirect()
        ->assertNotDispatched('open-kkiapay');

    expect(session('cart'))->toBe($before)
        ->and($cart->count())->toBe(2)
        ->and($product->fresh()->stock)->toBe($remaining)
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
    Notification::assertNothingSent();

    $cart->remove($product->id.':42');
    if ($remaining === 1) {
        $component->call('place')->assertHasNoErrors('cart')->assertRedirect();
        expect(Order::count())->toBe(1)->and($product->fresh()->stock)->toBe(0);
    } else {
        $cart->remove($product->id.':41');
        expect($cart->isEmpty())->toBeTrue();
    }
})->with([1, 0]);

it('refuse toute la commande si un seul produit manque sans consommer les autres stocks', function (): void {
    $first = Product::create(['name' => 'Disponible', 'slug' => 'stock-disponible', 'price' => 1000, 'stock' => 5, 'is_active' => true]);
    $second = Product::create(['name' => 'Dernier article', 'slug' => 'stock-dernier', 'price' => 1000, 'stock' => 1, 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($first->id, 2);
    $cart->add($second->id);
    $before = session('cart');
    $second->update(['stock' => 0]);

    expect(fn () => app(OrderService::class)->place(buyer(), [
        'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
    ]))->toThrow(ValidationException::class);
    expect($first->fresh()->stock)->toBe(5)
        ->and($second->fresh()->stock)->toBe(0)
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and(session('cart'))->toBe($before);
    Notification::assertNothingSent();
});

it('utilise les produits relus sous verrou même si la lecture initiale du panier est périmée', function (): void {
    $product = Product::create(['name' => 'Article stock', 'slug' => 'stock-lecture-perimee', 'price' => 1000, 'stock' => 2, 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 2);
    $snapshot = $cart->items();
    $product->update(['stock' => 1]);
    $staleCart = Mockery::mock(CartService::class)->makePartial();
    $staleCart->shouldReceive('items')->once()->andReturn($snapshot);

    expect(fn () => (new OrderService($staleCart))->place(buyer(), [
        'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
    ]))->toThrow(ValidationException::class);
    expect($product->fresh()->stock)->toBe(1)->and(Order::count())->toBe(0);
    Notification::assertNothingSent();
});

it('crée les lignes de variantes et respecte le stock global géré ou non géré', function (?int $stock): void {
    $product = Product::create(['name' => 'Article stock', 'slug' => 'stock-commande-valide', 'price' => 1000, 'stock' => $stock, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 2, '41');
    $cart->add($product->id, 1, '42');
    $order = app(OrderService::class)->place(buyer(), [
        'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
    ]);

    expect($order->items)->toHaveCount(2)
        ->and($order->items->pluck('size')->all())->toBe(['41', '42'])
        ->and($order->items()->sum('quantity'))->toBe(3)
        ->and($order->subtotal)->toBe(3000)
        ->and($product->fresh()->stock)->toBe($stock === null ? null : 0)
        ->and($cart->isEmpty())->toBeTrue();
})->with([3, null]);

it('restaure tous les stocks et le panier si une erreur survient pendant le décrément', function (): void {
    $first = Product::create(['name' => 'Premier', 'slug' => 'stock-rollback-1', 'price' => 1000, 'stock' => 2, 'is_active' => true]);
    $second = Product::create(['name' => 'Second', 'slug' => 'stock-rollback-2', 'price' => 1000, 'stock' => 2, 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($first->id);
    $cart->add($second->id);
    $before = session('cart');
    Event::listen('eloquent.updated: '.Product::class, function (Product $product) use ($second): void {
        if ($product->id === $second->id) {
            throw new RuntimeException('Échec stock simulé');
        }
    });

    expect(fn () => app(OrderService::class)->place(buyer(), [
        'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
    ]))->toThrow(RuntimeException::class, 'Échec stock simulé');
    expect($first->fresh()->stock)->toBe(2)
        ->and($second->fresh()->stock)->toBe(2)
        ->and(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0)
        ->and(session('cart'))->toBe($before);
    Notification::assertNothingSent();
});

it('permet au client de confirmer la réception', function (): void {
    $user = buyer();
    $order = Order::create([
        'number' => 'SZ-CMD-2026-00001', 'user_id' => $user->id, 'status' => OrderStatus::Delivered,
        'subtotal' => 8000, 'total' => 8000, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);

    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->call('confirmReceived');

    expect($order->fresh()->received_confirmed_at)->not->toBeNull();
});

it('fait avancer une commande côté admin (transition gardée)', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $order = Order::create([
        'number' => 'SZ-CMD-2026-00002', 'user_id' => buyer()->id, 'status' => OrderStatus::Pending,
        'subtotal' => 5000, 'total' => 5000, 'payment_method' => 'cod', 'payment_status' => 'unpaid', 'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);

    $component = Volt::actingAs($admin)->test('pages.admin.boutique.orders')->call('open', $order->id);
    $component->call('setStatus', 'confirmed');
    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    // transition illégale ignorée (confirmed -> delivered direct)
    $component->call('setStatus', 'delivered');
    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});
