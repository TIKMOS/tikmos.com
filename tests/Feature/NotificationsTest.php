<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\OrderReceived;
use App\Notifications\OrderStatusChanged;
use App\Notifications\QuoteSent;
use App\Notifications\ServiceRequestReceived;
use App\Notifications\ServiceRequestStatusChanged;
use App\Notifications\ServiceRequestSubmitted;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function notifUser(string $role): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole($role);

    return $u;
}

it('notifie l’équipe et le client à la soumission d’une demande', function (): void {
    Notification::fake();
    $service = Service::factory()->create();

    Volt::test('pages.public.services-show', ['service' => $service])
        ->set('name', 'Awa')
        ->set('email', 'awa@example.com')
        ->set('message', 'Besoin d\'une formation.')
        ->call('submit')
        ->assertHasNoErrors();

    Notification::assertSentOnDemand(ServiceRequestReceived::class);
    Notification::assertSentOnDemand(ServiceRequestSubmitted::class);
});

it('notifie le client à l’envoi du devis', function (): void {
    Notification::fake();
    $client = notifUser(Role::Client->value);
    $request = ServiceRequest::factory()->create(['user_id' => $client->id]);

    Volt::actingAs(notifUser(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 500000)
        ->call('saveQuote')
        ->assertHasNoErrors();

    Notification::assertSentTo($client, QuoteSent::class);
});

it('notifie le client au changement de statut de sa demande', function (): void {
    Notification::fake();
    $client = notifUser(Role::Client->value);
    $request = ServiceRequest::factory()->create(['user_id' => $client->id]);

    Volt::actingAs(notifUser(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->call('transition', ServiceRequestStatus::Studying->value);

    Notification::assertSentTo($client, ServiceRequestStatusChanged::class);
});

it('notifie l’équipe à la création d’une commande', function (): void {
    Notification::fake();
    $buyer = notifUser(Role::Client->value);
    $p = Product::create(['name' => 'Soulier', 'slug' => 'soulier-n', 'price' => 100000, 'stock' => 5, 'is_active' => true]);
    app(CartService::class)->add($p->id, 1);

    Volt::actingAs($buyer)->test('pages.public.checkout')
        ->set('contact_name', 'Acheteur')
        ->set('contact_phone', '+229 90000000')
        ->set('delivery_address', 'Rue 1')
        ->set('delivery_city', 'Cotonou')
        ->call('place');

    Notification::assertSentOnDemand(OrderReceived::class);
});

it('notifie le client au changement de statut de sa commande', function (): void {
    Notification::fake();
    $buyer = notifUser(Role::Client->value);
    $order = Order::create([
        'number' => 'TKM-CMD-2026-09999', 'user_id' => $buyer->id, 'status' => OrderStatus::Pending,
        'payment_method' => 'cod', 'payment_status' => 'unpaid', 'subtotal' => 5000, 'total' => 5000,
        'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);

    $admin = notifUser(Role::Admin->value);
    Volt::actingAs($admin)->test('pages.admin.boutique.orders')
        ->call('open', $order->id)
        ->call('setStatus', OrderStatus::Confirmed->value);

    Notification::assertSentTo($buyer, OrderStatusChanged::class);
});
