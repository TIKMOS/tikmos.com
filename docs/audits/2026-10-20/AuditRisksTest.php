<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\Enums\PaymentProvider;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(Tests\TestCase::class, RefreshDatabase::class);

function auditRiskOrder(User $user, string $number): Order
{
    return Order::create([
        'number' => $number,
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
        'subtotal' => 16000,
        'total' => 16000,
        'payment_method' => 'online',
        'payment_status' => 'unpaid',
        'contact_name' => 'Audit',
        'contact_phone' => '+2290190000000',
        'delivery_address' => 'Adresse de test',
    ]);
}

it('audit reproduit deux commandes payees avec la meme reference externe', function (): void {
    Http::preventStrayRequests();
    Http::fake(['*/api/v1/transactions/status' => Http::response(['status' => 'SUCCESS', 'amount' => 16000])]);
    $buyer = User::factory()->create(['status' => 'active']);
    $service = app(PaymentService::class);
    $first = auditRiskOrder($buyer, 'AUDIT-PAY-1');
    $second = auditRiskOrder($buyer, 'AUDIT-PAY-2');
    expect($service->verify($service->open($first, PaymentProvider::Kkiapay), 'SAME-EXTERNAL-TXN'))->toBeTrue();
    expect($service->verify($service->open($second, PaymentProvider::Kkiapay), 'SAME-EXTERNAL-TXN'))->toBeTrue();
    expect($first->fresh()->payment_status->value)->toBe('paid');
    expect($second->fresh()->payment_status->value)->toBe('paid');
});

it('audit reproduit un paiement accepte sans montant confirme', function (): void {
    Http::preventStrayRequests();
    Http::fake(['*/api/v1/transactions/status' => Http::response(['status' => 'SUCCESS'])]);
    $buyer = User::factory()->create(['status' => 'active']);
    $order = auditRiskOrder($buyer, 'AUDIT-PAY-NO-AMOUNT');
    $service = app(PaymentService::class);
    expect($service->verify($service->open($order, PaymentProvider::Kkiapay), 'TXN-NO-AMOUNT'))->toBeTrue();
    expect($order->fresh()->payment_status->value)->toBe('paid');
});

it('audit reproduit un stock negatif avec deux variantes du meme produit', function (): void {
    Notification::fake();
    $buyer = User::factory()->create(['status' => 'active']);
    $product = Product::create(['name' => 'Produit audit', 'slug' => 'audit-stock', 'price' => 1000, 'stock' => 2, 'sizes' => [41, 42], 'is_active' => true]);
    $cart = app(CartService::class);
    $cart->add($product->id, 2, '41');
    $cart->add($product->id, 2, '42');
    $order = app(OrderService::class)->place($buyer, [
        'contact_name' => 'Audit', 'contact_phone' => '+2290190000000', 'delivery_address' => 'Test uniquement',
    ]);
    expect($order->items()->sum('quantity'))->toBe(4);
    expect($product->fresh()->stock)->toBe(-2);
});

it('audit reproduit un devis expire encore acceptable', function (): void {
    $buyer = User::factory()->create(['status' => 'active']);
    $request = App\Models\ServiceRequest::factory()->create([
        'user_id' => $buyer->id,
        'status' => App\Enums\ServiceRequestStatus::Quoted,
    ]);
    $quote = App\Models\Quote::create([
        'service_request_id' => $request->id,
        'reference' => 'AUDIT-EXPIRED-QUOTE',
        'amount' => 10000,
        'currency' => 'XOF',
        'status' => App\Enums\QuoteStatus::Sent,
        'sent_at' => now()->subDays(5),
        'valid_until' => now()->subDay(),
    ]);
    Livewire\Volt\Volt::actingAs($buyer)
        ->test('pages.app.requests-show', ['request' => $request])
        ->call('acceptQuote');
    expect($quote->fresh()->status)->toBe(App\Enums\QuoteStatus::Accepted);
});

it('audit confirme que la demande invite reste inaccessible apres connexion du meme email', function (): void {
    $buyer = User::factory()->create(['status' => 'active', 'email' => 'audit-guest@example.test']);
    $request = App\Models\ServiceRequest::factory()->create([
        'user_id' => null,
        'email' => $buyer->email,
    ]);
    $this->actingAs($buyer)->get(route('app.requests.show', $request->reference))->assertForbidden();
});
