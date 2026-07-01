<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use App\Services\CartService;
use App\Services\PaymentService;
use App\Settings\PaymentSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    // Clés KKiaPay factices (les appels réseau sont mockés via Http::fake).
    config([
        'payments.gateways.kkiapay.public_key' => 'pk_test',
        'payments.gateways.kkiapay.private_key' => 'sk_test',
        'payments.gateways.kkiapay.secret' => 'whsec_test',
        'payments.gateways.kkiapay.sandbox' => true,
    ]);
});

function payBuyer(): User
{
    $u = User::factory()->create(['status' => 'active', 'phone' => '+229 90000000', 'city' => 'Cotonou']);
    $u->assignRole(Role::Client->value);

    return $u;
}

function payOrder(int $total = 16000): Order
{
    return Order::create([
        'number' => 'TKM-CMD-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        'user_id' => payBuyer()->id,
        'status' => OrderStatus::Pending,
        'subtotal' => $total,
        'total' => $total,
        'payment_method' => 'online',
        'payment_status' => 'unpaid',
        'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);
}

function fakeKkiapay(string $status, ?int $amount, string $source = 'MOBILE_MONEY'): void
{
    Http::fake([
        '*/api/v1/transactions/status' => Http::response(array_filter([
            'status' => $status,
            'amount' => $amount,
            'source' => $source,
        ], fn ($v) => $v !== null)),
    ]);
}

it('confirme un paiement en ligne vérifié côté serveur (idempotent)', function (): void {
    $order = payOrder(16000);
    fakeKkiapay('SUCCESS', 16000);

    $payments = app(PaymentService::class);
    $payment = $payments->open($order, PaymentProvider::Kkiapay);

    expect($payments->verify($payment, 'TXN-1'))->toBeTrue();

    $payment->refresh();
    expect($payment->state)->toBe(PaymentState::Succeeded)
        ->and($payment->method)->toBe('momo')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($order->fresh()->payment_status->value)->toBe('paid')
        ->and($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    // Rejeu (retour widget + webhook) : pas de double transition.
    expect($payments->verify($payment, 'TXN-1'))->toBeFalse()
        ->and($payment->fresh()->events()->where('type', 'succeeded')->count())->toBe(1);
});

it('rejette un paiement échoué', function (): void {
    $order = payOrder();
    fakeKkiapay('FAILED', null);

    $payments = app(PaymentService::class);
    $payment = $payments->open($order, PaymentProvider::Kkiapay);

    expect($payments->verify($payment, 'TXN-2'))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Failed)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid');
});

it('refuse un montant incohérent (anti-fraude)', function (): void {
    $order = payOrder(16000);
    fakeKkiapay('SUCCESS', 10000); // montant trafiqué

    $payments = app(PaymentService::class);
    $payment = $payments->open($order, PaymentProvider::Kkiapay);

    expect($payments->verify($payment, 'TXN-3'))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->fresh()->events()->where('type', 'amount_mismatch')->count())->toBe(1);
});

it('traite un webhook signé et confirme la commande', function (): void {
    $order = payOrder(16000);
    fakeKkiapay('SUCCESS', 16000);

    $payment = app(PaymentService::class)->open($order, PaymentProvider::Kkiapay);

    $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', [
            'transactionId' => 'TXN-HOOK',
            'data' => json_encode(['reference' => $payment->reference]),
        ])
        ->assertOk()
        ->assertJson(['handled' => true]);

    expect($order->fresh()->payment_status->value)->toBe('paid');
});

it('rejette un webhook à signature invalide', function (): void {
    $order = payOrder(16000);
    $payment = app(PaymentService::class)->open($order, PaymentProvider::Kkiapay);

    $this->withHeaders(['x-kkiapay-secret' => 'mauvais-secret'])
        ->postJson('/webhooks/payments/kkiapay', [
            'transactionId' => 'TXN-HOOK',
            'data' => json_encode(['reference' => $payment->reference]),
        ])
        ->assertOk()
        ->assertJson(['handled' => false]);

    expect($order->fresh()->payment_status->value)->toBe('unpaid');
});

it('déclenche le widget KKiaPay au checkout en mode en ligne', function (): void {
    $s = app(PaymentSettings::class);
    $s->mode = 'online';
    $s->kkiapay_public_key = 'pk_test';
    $s->save();

    $user = payBuyer();
    $p = Product::create(['name' => 'Casque', 'slug' => 'casque', 'price' => 8000, 'stock' => 5, 'is_active' => true]);
    app(CartService::class)->add($p->id, 2);

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Jean')
        ->set('contact_phone', '+229 91000000')
        ->set('delivery_address', 'Rue 12')
        ->call('place')
        ->assertDispatched('open-kkiapay')
        ->assertNoRedirect();

    $order = Order::where('user_id', $user->id)->first();
    expect($order->payment_method->value)->toBe('online')
        ->and($order->payments()->where('state', 'pending')->count())->toBe(1);
});

it('laisse le paiement à la livraison fonctionner en mode mixte', function (): void {
    $s = app(PaymentSettings::class);
    $s->mode = 'both';
    $s->kkiapay_public_key = 'pk_test';
    $s->save();

    $user = payBuyer();
    $p = Product::create(['name' => 'Souris', 'slug' => 'souris', 'price' => 5000, 'stock' => 5, 'is_active' => true]);
    app(CartService::class)->add($p->id, 1);

    Volt::actingAs($user)->test('pages.public.checkout')
        ->set('payment_method', 'cod')
        ->set('contact_name', 'Jean')
        ->set('contact_phone', '+229 91000000')
        ->set('delivery_address', 'Rue 12')
        ->call('place')
        ->assertRedirect();

    expect(Order::where('user_id', $user->id)->first()->payment_method->value)->toBe('cod');
});
