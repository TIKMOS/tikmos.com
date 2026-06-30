<?php

use App\Enums\OrderStatus;
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
