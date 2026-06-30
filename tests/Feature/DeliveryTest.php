<?php

use App\Enums\CommissionType;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CommissionCalculator;
use App\Services\DeliveryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function makeOrder(): Order
{
    $u = User::factory()->create();
    $u->assignRole(Role::Client->value);

    return Order::create([
        'number' => 'SZ-CMD-2026-0'.random_int(1000, 9999), 'user_id' => $u->id,
        'status' => OrderStatus::Confirmed, 'payment_method' => 'cod', 'payment_status' => 'unpaid',
        'subtotal' => 20000, 'total' => 20000, 'contact_name' => 'C', 'contact_phone' => 'P', 'delivery_address' => 'Adr',
    ]);
}

it('calcule la commission selon les 3 modes', function (): void {
    $order = makeOrder(); // total 20000
    $calc = new CommissionCalculator;

    $percent = Courier::create(['name' => 'A', 'slug' => 'a', 'commission_type' => CommissionType::Percent, 'commission_value' => 10]);
    $flat = Courier::create(['name' => 'B', 'slug' => 'b', 'commission_type' => CommissionType::Flat, 'commission_value' => 1500]);
    $perDel = Courier::create(['name' => 'C', 'slug' => 'c', 'commission_type' => CommissionType::PerDelivery, 'commission_value' => 800]);

    expect($calc->compute($percent, $order))->toBe(2000)
        ->and($calc->compute($flat, $order))->toBe(1500)
        ->and($calc->compute($perDel, $order))->toBe(800);
});

it('crée et affecte une livraison, puis calcule la commission à la livraison', function (): void {
    $order = makeOrder();
    $courier = Courier::create(['name' => 'DDL', 'slug' => 'ddl', 'commission_type' => CommissionType::PerDelivery, 'commission_value' => 1000]);
    $service = app(DeliveryService::class);

    $delivery = $service->ensureForOrder($order, $courier);
    expect($delivery->status)->toBe(DeliveryStatus::Assigned)
        ->and($delivery->courier_id)->toBe($courier->id);

    $service->transition($delivery, DeliveryStatus::PickedUp);
    $service->transition($delivery, DeliveryStatus::EnRoute);
    $service->transition($delivery, DeliveryStatus::Delivered);

    $delivery->refresh();
    expect($delivery->status)->toBe(DeliveryStatus::Delivered)
        ->and($delivery->commission_amount)->toBe(1000)
        ->and($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('refuse une transition de livraison illégale', function (): void {
    $order = makeOrder();
    $courier = Courier::create(['name' => 'X', 'slug' => 'x', 'commission_type' => CommissionType::None, 'commission_value' => 0]);
    $delivery = app(DeliveryService::class)->ensureForOrder($order, $courier);

    // assigned -> delivered direct = interdit
    expect(app(DeliveryService::class)->transition($delivery, DeliveryStatus::Delivered))->toBeFalse();
});

it('cloisonne le livreur à ses livraisons', function (): void {
    $courier = Courier::create(['name' => 'DDL', 'slug' => 'ddl2', 'commission_type' => CommissionType::PerDelivery, 'commission_value' => 500]);
    $livreur = User::factory()->create(['courier_id' => $courier->id]);
    $livreur->assignRole(Role::Livreur->value);
    $other = User::factory()->create();
    $other->assignRole(Role::Livreur->value);

    $mine = app(DeliveryService::class)->ensureForOrder(makeOrder(), $courier);
    $mine->update(['livreur_id' => $livreur->id]);
    $theirs = app(DeliveryService::class)->ensureForOrder(makeOrder(), $courier);
    $theirs->update(['livreur_id' => $other->id]);

    Volt::actingAs($livreur)->test('pages.livreur.deliveries')
        ->assertSee($mine->order->number)
        ->assertDontSee($theirs->order->number);

    // ne peut pas agir sur la livraison d'un autre (findOrFail cloisonné)
    expect(fn () => Volt::actingAs($livreur)->test('pages.livreur.deliveries')->call('advance', $theirs->id, 'picked_up'))
        ->toThrow(ModelNotFoundException::class);
});

it('checkout : SEZY par défaut (sans transporteur) crée la commande et la livraison', function (): void {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole(Role::Client->value);
    $product = Product::create(['name' => 'Prod', 'slug' => 'prod-ck', 'price' => 6000, 'is_active' => true]);
    app(CartService::class)->add($product->id, 1);

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Client')
        ->set('contact_phone', '+229 90000000')
        ->set('delivery_address', 'Rue 1')
        ->set('courier_id', '') // SEZY interne par défaut
        ->call('place')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->courier_id)->toBeNull()
        ->and($order->delivery)->not->toBeNull()
        ->and($order->delivery->status)->toBe(DeliveryStatus::Pending);
});

it('checkout : sélection d’un transporteur affecte la livraison', function (): void {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole(Role::Client->value);
    $courier = Courier::create(['name' => 'DDL', 'slug' => 'ddl-ck', 'commission_type' => CommissionType::PerDelivery, 'commission_value' => 500, 'is_active' => true]);
    $product = Product::create(['name' => 'Prod2', 'slug' => 'prod-ck2', 'price' => 6000, 'is_active' => true]);
    app(CartService::class)->add($product->id, 1);

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Client')
        ->set('contact_phone', '+229 90000000')
        ->set('delivery_address', 'Rue 1')
        ->set('courier_id', (string) $courier->id)
        ->call('place')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();
    expect($order->courier_id)->toBe($courier->id)
        ->and($order->delivery->status)->toBe(DeliveryStatus::Assigned);
});

it('invite un livreur depuis l’admin transporteurs', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $courier = Courier::create(['name' => 'DDL', 'slug' => 'ddl3', 'commission_type' => CommissionType::PerDelivery, 'commission_value' => 500]);

    Volt::actingAs($admin)->test('pages.admin.couriers')
        ->set('livreurCourierId', $courier->id)
        ->set('livreurName', 'Nouveau Livreur')
        ->set('livreurEmail', 'newlivreur@sezy.test')
        ->call('inviteLivreur')
        ->assertHasNoErrors();

    $u = User::where('email', 'newlivreur@sezy.test')->first();
    expect($u)->not->toBeNull()
        ->and($u->hasRole(Role::Livreur->value))->toBeTrue()
        ->and($u->courier_id)->toBe($courier->id);
});
