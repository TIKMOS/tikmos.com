<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Payments\Enums\PaymentState;
use App\Services\CartService;
use App\Services\OrderReservationService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Settings\PaymentSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
    $this->travelTo(now()->startOfSecond());
    config(['payments.gateways.kkiapay' => [
        'public_key' => 'public-fictif', 'private_key' => 'private-fictif',
        'secret' => 'secret-fictif', 'sandbox' => true,
    ]]);
    $settings = app(PaymentSettings::class);
    $settings->mode = 'both';
    $settings->kkiapay_public_key = '';
    $settings->kkiapay_private_key = '';
    $settings->kkiapay_secret = '';
});

/** @return array{Order, Product, User} */
function recoveryOrder(?int $stock = 2): array
{
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole(Role::Client->value);
    $product = Product::create([
        'name' => 'Article fictif', 'slug' => 'recette-'.bin2hex(random_bytes(6)),
        'price' => 1000, 'stock' => $stock, 'is_active' => true,
    ]);
    app(CartService::class)->add($product->id);
    $order = app(OrderService::class)->place($user, [
        'contact_name' => 'Client fictif', 'contact_phone' => '+2290190000000',
        'delivery_address' => 'Adresse fictive',
    ], PaymentMethod::Online);

    return [$order, $product, $user];
}

it('F07 limite la réservation du stock en ligne à quinze minutes', function (): void {
    [$order, $product] = recoveryOrder();

    expect($product->fresh()->stock)->toBe(1)
        ->and($order->reservation_expires_at?->toDateTimeString())->toBe(now()->addMinutes(15)->toDateTimeString());
});

it('libère le stock une seule fois à la limite exacte et refuse la reprise', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $this->travelTo($order->reservation_expires_at->copy()->subSecond());
    expect(app(OrderReservationService::class)->expire($order))->toBeFalse();
    $this->travelTo($order->reservation_expires_at);
    expect(fn () => app(PaymentService::class)->resumeOrder($order, $user))->toThrow(ValidationException::class);
    expect($product->fresh()->stock)->toBe(2)
        ->and($order->fresh()->reservation_released_at)->not->toBeNull();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    expect(app(OrderReservationService::class)->expire($order))->toBeFalse()
        ->and($product->fresh()->stock)->toBe(2)
        ->and(Payment::count())->toBe(0);
});

it('reprend le même paiement et la même commande sans prolonger la réservation', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $deadline = $order->reservation_expires_at->toDateTimeString();
    $service = app(PaymentService::class);
    $first = $service->resumeOrder($order, $user);
    $this->travel(3)->minutes();
    $second = $service->resumeOrder($order->fresh(), $user);
    expect($second->id)->toBe($first->id)->and($second->amount)->toBe($order->total)
        ->and(Order::count())->toBe(1)->and(Payment::count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(1)
        ->and($order->fresh()->reservation_expires_at->toDateTimeString())->toBe($deadline);
    Http::assertNothingSent();
});

it('permet une reprise après échec confirmé sans créer un autre paiement', function (): void {
    [$order, $product, $user] = recoveryOrder();
    Http::fakeSequence()->push(['status' => 'FAILED'])->push(['status' => 'SUCCESS', 'amount' => $order->total]);
    $service = app(PaymentService::class);
    $payment = $service->resumeOrder($order, $user);
    expect($service->verify($payment, 'REF-FAILED'))->toBeFalse();
    $retry = $service->resumeOrder($order, $user);
    expect($retry->id)->toBe($payment->id)->and($service->verify($retry, 'REF-RETRY'))->toBeTrue()
        ->and(Payment::count())->toBe(1)->and($product->fresh()->stock)->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

it('une fermeture ou erreur du navigateur conserve le paiement et le stock', function (string $reason): void {
    [$order, $product, $user] = recoveryOrder();
    $payment = app(PaymentService::class)->resumeOrder($order, $user);
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->call('widgetInterrupted', $reason)->assertSee('Si vous avez été débité')
        ->assertSee('Reprendre le paiement');
    expect($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($product->fresh()->stock)->toBe(1)->and(Order::count())->toBe(1);
    Http::assertNothingSent();
})->with(['closed', 'failed']);

it('retrouve le même paiement après rafraîchissement du détail', function (): void {
    [$order, $product, $user] = recoveryOrder();
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->call('resumePayment')->assertDispatched('open-order-payment');
    $reference = Payment::sole()->reference;
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order->fresh()])
        ->call('resumePayment')->call('resumePayment')->assertDispatched('open-order-payment');
    expect(Payment::count())->toBe(1)->and(Payment::sole()->reference)->toBe($reference)
        ->and($product->fresh()->stock)->toBe(1);
});

it('revérifie une réservation expirée après ouverture de la page', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $component = Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->assertSee('Reprendre le paiement');
    $this->travel(16)->minutes();
    $component->call('resumePayment')->assertHasErrors('payment')
        ->assertSee('Réservation expirée')->assertDontSee('Reprendre le paiement');
    expect($product->fresh()->stock)->toBe(2)->and(Payment::count())->toBe(0);
});

it('refuse un choix en ligne devenu indisponible sans le convertir silencieusement en COD', function (): void {
    [$seed, $product, $user] = recoveryOrder();
    app(CartService::class)->add($product->id);
    $component = Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Client fictif')->set('contact_phone', '+2290190000000')->set('delivery_address', 'Fictive');
    config(['payments.gateways.kkiapay.secret' => '']);
    $component->call('place')->assertHasErrors('payment_method')->assertSee('paiement en ligne est actuellement indisponible');
    expect(Order::count())->toBe(1)->and(app(CartService::class)->count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(1);
});

it('confirme avant expiration puis ignore expiration et webhooks rejoués', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $service = app(PaymentService::class);
    $payment = $service->resumeOrder($order, $user);
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'amount' => $order->total])]);
    expect($service->verify($payment, 'REF-PAID'))->toBeTrue();
    $this->travel(16)->minutes();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    $this->withHeader('x-kkiapay-secret', 'secret-fictif')->postJson('/webhooks/payments/kkiapay', [
        'transactionId' => 'REF-PAID', 'data' => ['reference' => $payment->reference],
    ])->assertOk()->assertJson(['handled' => false]);
    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->fresh()->reservation_released_at)->toBeNull()
        ->and($order->fresh()->payment_review_required)->toBeFalse()
        ->and($product->fresh()->stock)->toBe(1)
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(1)
        ->and($payment->events()->where('type', 'stock_consumed')->count())->toBe(1);
});

it('signale un succès tardif sans consommer un stock libéré ou épuisé', function (bool $expireFirst): void {
    [$order, $product, $user] = recoveryOrder(1);
    $service = app(PaymentService::class);
    $payment = $service->resumeOrder($order, $user);
    $this->travel(16)->minutes();
    if ($expireFirst) {
        app(OrderReservationService::class)->expire($order);
        $product->refresh()->update(['stock' => 0]); // Une autre vente après libération.
    }
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'amount' => $order->total])]);
    $this->withHeader('x-kkiapay-secret', 'secret-fictif')->postJson('/webhooks/payments/kkiapay', [
        'transactionId' => 'REF-LATE', 'data' => ['reference' => $payment->reference],
    ])->assertOk()->assertJson(['handled' => true]);
    expect($payment->fresh()->state)->toBe(PaymentState::Succeeded)
        ->and($order->fresh()->payment_status->value)->toBe('paid')
        ->and($order->fresh()->payment_review_required)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->fresh()->confirmed_at)->toBeNull()
        ->and($product->fresh()->stock)->toBe($expireFirst ? 0 : 1);
    expect($service->verify($payment, 'REF-LATE'))->toBeFalse();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    expect($product->fresh()->stock)->toBe($expireFirst ? 0 : 1)
        ->and($payment->events()->where('type', 'stock_review_required')->count())->toBe(1);
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order->fresh()])
        ->assertSee('Paiement reçu — vérification nécessaire')->assertDontSee('Reprendre le paiement');
})->with([true, false]);

it('refuse le succès incomplet ou incohérent même à expiration', function (?int $amount): void {
    [$order, $product, $user] = recoveryOrder();
    $service = app(PaymentService::class);
    $payment = $service->resumeOrder($order, $user);
    $this->travel(16)->minutes();
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'amount' => $amount])]);
    expect($service->verify($payment, 'REF-INVALID'))->toBeFalse()
        ->and($order->fresh()->payment_status->value)->toBe('unpaid');
    app(OrderReservationService::class)->expire($order);
    expect($product->fresh()->stock)->toBe(2);
})->with([null, 999, 0]);

it('refuse une reprise lorsque le stock a été libéré sans le réserver à nouveau', function (): void {
    [$order, $product, $user] = recoveryOrder(1);
    $this->travel(16)->minutes();
    app(OrderReservationService::class)->expire($order);
    $product->refresh()->update(['stock' => 0]);
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->assertSee('Réservation expirée')->assertDontSee('Reprendre le paiement')
        ->call('resumePayment')->assertHasErrors('payment');
    expect($product->fresh()->stock)->toBe(0)->and(Payment::count())->toBe(0);
});

it('interdit les accès croisés sans affecter la réservation légitime', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $other = User::factory()->create();
    $other->assignRole(Role::Client->value);
    expect(fn () => app(PaymentService::class)->resumeOrder($order, $other))
        ->toThrow(HttpException::class);
    Volt::actingAs($other)->test('pages.app.orders-show', ['order' => $order])->assertForbidden();
    expect($product->fresh()->stock)->toBe(1)->and(Payment::count())->toBe(0);
    expect(app(PaymentService::class)->resumeOrder($order, $user))->toBeInstanceOf(Payment::class);
});

it('refuse les clés manquantes invalides ou une devise incompatible avant réservation', function (string $key, mixed $value): void {
    config([$key => $value]);
    $user = User::factory()->create();
    $product = Product::create(['name' => 'Fictif', 'slug' => 'missing-keys', 'price' => 1000, 'stock' => 2, 'is_active' => true]);
    app(CartService::class)->add($product->id);
    expect(app(PaymentSettings::class)->onlineEnabled())->toBeFalse();
    expect(fn () => app(OrderService::class)->place($user, [
        'contact_name' => 'Fictif', 'contact_phone' => 'Test', 'delivery_address' => 'Fictive',
    ], PaymentMethod::Online))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(2)
        ->and(app(CartService::class)->count())->toBe(1);
})->with([
    ['payments.gateways.kkiapay.public_key', null], ['payments.gateways.kkiapay.private_key', ''],
    ['payments.gateways.kkiapay.secret', '   '], ['payments.gateways.kkiapay.secret', ['invalid']],
    ['payments.gateways.kkiapay.public_key', 'clé avec espaces'], ['payments.currency', 'EUR'],
]);

it('préserve le COD sans clés et sans expiration de son stock', function (): void {
    [$order, $product, $user] = recoveryOrder();
    // La première commande reste en ligne ; on crée une commande COD distincte.
    config(['payments.gateways.kkiapay' => []]);
    app(CartService::class)->add($product->id);
    $cod = app(OrderService::class)->place($user, ['contact_name' => 'Fictif', 'contact_phone' => 'Test', 'delivery_address' => 'Fictive']);
    $this->travel(16)->minutes();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    expect($cod->reservation_expires_at)->toBeNull()->and($cod->stock_reservation)->toBeNull()
        ->and($product->fresh()->stock)->toBe(1); // Seule la première réservation a été libérée.
});

it('préserve les stocks non gérés lors de l’expiration', function (): void {
    [$order, $product] = recoveryOrder(null);
    $this->travel(16)->minutes();
    expect(app(OrderReservationService::class)->expire($order))->toBeTrue()
        ->and($order->stock_reservation)->toBe([])->and($product->fresh()->stock)->toBeNull();
});

it('cumule les variantes pour une restitution unique du stock global', function (): void {
    [$first, $product, $user] = recoveryOrder(5);
    $product->update(['sizes' => [41, 42]]);
    app(CartService::class)->add($product->id, 2, '41');
    app(CartService::class)->add($product->id, 2, '42');
    $order = app(OrderService::class)->place($user, ['contact_name' => 'Fictif', 'contact_phone' => 'Test', 'delivery_address' => 'Fictive'], PaymentMethod::Online);
    expect($product->fresh()->stock)->toBe(0);
    $this->travel(16)->minutes();
    app(OrderReservationService::class)->expire($order);
    app(OrderReservationService::class)->expire($order);
    expect($product->fresh()->stock)->toBe(4);
});

it('annule toute restitution partielle si une écriture de stock échoue', function (): void {
    [$order, $product] = recoveryOrder();
    $this->travel(16)->minutes();
    Event::listen('eloquent.updated: '.Product::class, function (): void {
        throw new RuntimeException('Écriture simulée');
    });
    expect(fn () => app(OrderReservationService::class)->expire($order))->toThrow(RuntimeException::class);
    expect($product->fresh()->stock)->toBe(1)->and($order->fresh()->reservation_released_at)->toBeNull();
});

it('ne réaffecte ni ne libère silencieusement une ancienne commande', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $order->update(['reservation_expires_at' => null, 'stock_reservation' => null]);
    $this->travel(60)->minutes();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->assertSee('Commande antérieure')->call('resumePayment')->assertHasErrors('payment');
    expect($product->fresh()->stock)->toBe(1)->and($order->fresh()->payment_status->value)->toBe('unpaid');
});

it('ne crée pas deux commandes ni notifications au rejeu du checkout', function (): void {
    [$seed, $product, $user] = recoveryOrder(3);
    app(CartService::class)->add($product->id);
    $component = Volt::actingAs($user)->test('pages.public.checkout')
        ->set('contact_name', 'Client fictif')->set('contact_phone', '+2290190000000')->set('delivery_address', 'Fictive');
    $component->call('place')->assertRedirect();
    $new = Order::latest('id')->first();
    app(CartService::class)->add($product->id); // Nouveau panier à ne pas effacer lors du rejeu.
    $component->call('place')->assertRedirect();
    expect(Order::count())->toBe(2)->and(Payment::count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(1)->and(app(CartService::class)->count())->toBe(1)
        ->and(Order::latest('id')->first()->id)->toBe($new->id);
    Notification::assertCount(2); // Une notification par commande réellement créée.
});

it('gère une indisponibilité réseau sans faux échec bancaire', function (): void {
    [$order, $product, $user] = recoveryOrder();
    $payment = app(PaymentService::class)->resumeOrder($order, $user);
    Http::fake(fn () => throw new ConnectionException('Erreur simulée'));
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])
        ->call('confirmOnline', $payment->reference, 'REF-TIMEOUT')->assertSee('Vérification non concluante');
    expect($payment->fresh()->state)->toBe(PaymentState::Pending)->and($product->fresh()->stock)->toBe(1);
});

it('interdit la confirmation manuelle et l’encaissement COD d’une commande en ligne à vérifier', function (): void {
    [$order, $product] = recoveryOrder();
    $order->update(['payment_review_required' => true, 'payment_status' => 'paid']);
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    Volt::actingAs($admin)->test('pages.admin.boutique.orders')->call('open', $order->id)
        ->assertSee('La préparation reste bloquée')->call('setStatus', 'confirmed');
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
    Volt::actingAs($admin)->test('pages.admin.boutique.orders')->call('open', $order->id)->call('markPaid')->assertStatus(422);
});

it('annule une commande en ligne et libère sa réservation une seule fois', function (): void {
    [$order, $product] = recoveryOrder();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $component = Volt::actingAs($admin)->test('pages.admin.boutique.orders')->call('open', $order->id);
    $component->call('setStatus', 'cancelled')->call('setStatus', 'cancelled');
    expect($product->fresh()->stock)->toBe(2)->and($order->fresh()->reservation_released_at)->not->toBeNull();
});
