<?php

declare(strict_types=1);

use App\Enums\DeliveryPricing;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderReceived;
use App\Payments\Data\ChargeResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Settings\DeliverySettings;
use App\Settings\PaymentSettings;
use App\Settings\ShopSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Http::preventStrayRequests();
    Notification::fake();
    config(['payments.gateways.kkiapay' => ['public_key' => 'public-fictif', 'private_key' => 'private-fictif', 'secret' => 'secret-fictif', 'sandbox' => true]]);
    app(PaymentSettings::class)->mode = 'both';
    $shop = app(ShopSettings::class);
    $shop->default_delivery_fee = 0;
    $shop->save();
});

function configureDelivery(string $mode, int $fee = 0): void
{
    $delivery = app(DeliverySettings::class);
    $delivery->mode = $mode;
    $delivery->save();
    $shop = app(ShopSettings::class);
    $shop->default_delivery_fee = $fee;
    $shop->save();
}

it('affiche et conserve les trois etats sans faire confiance aux montants client', function (string $mode, int $fee, string $label, int $total): void {
    configureDelivery($mode, $fee);
    [$user] = deliveryBasket();
    Volt::test('pages.public.cart')->assertSee($label)->assertSee($mode === 'unknown' ? 'Total hors livraison' : 'Total');
    Volt::actingAs($user)->test('pages.public.checkout')->assertSee($label)
        ->assertSet('payment_method', $mode === 'unknown' ? 'cod' : 'online');
    $order = app(OrderService::class)->place($user, deliveryAddress() + ['delivery_fee' => -100, 'total' => 1, 'delivery_pricing' => 'free']);
    expect($order->delivery_pricing)->toBe($mode)->and($order->delivery_fee)->toBe($fee)
        ->and($order->total)->toBe($total)->and($order->deliveryIsFinal())->toBe($mode !== 'unknown');
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])->assertSee($label)->assertSee($order->totalHeading());
    $mail = (new OrderReceived($order))->toMail(new stdClass);
    $lines = implode(' ', $mail->introLines).' '.implode(' ', $mail->outroLines);
    expect($lines)->toContain($order->totalHeading().' : '.$order->totalLabel(), 'Livraison : '.$order->deliveryLabel());
    if ($mode === 'unknown') {
        expect($lines)->toContain('avant tout paiement');
    }
})->with([
    ['unknown', 0, 'À déterminer', 1000], ['free', 0, 'Gratuite', 1000], ['fixed', 250, '250 FCFA', 1250],
]);

it('traite une configuration incoherente comme inconnue sans annoncer de gratuite', function (string $mode, int $fee): void {
    configureDelivery($mode, $fee);
    deliveryBasket();
    expect(app(ShopSettings::class)->deliveryPricing())->toBe(DeliveryPricing::Unknown)
        ->and(app(ShopSettings::class)->deliveryFee())->toBe(0);
    Volt::test('pages.public.cart')->assertSee('Total hors livraison')->assertSee('À déterminer')->assertDontSee('Gratuite');
})->with([['fixed', 0], ['fixed', -1], ['free', 250], ['unknown', 250], ['invalide', 0]]);

it('refuse le choix en ligne force au checkout sans ecriture ni notification', function (): void {
    [$user, $product] = deliveryBasket();
    Volt::actingAs($user)->test('pages.public.checkout')->set('contact_phone', 'Fictif')->set('delivery_address', 'Adresse fictive')
        ->set('payment_method', 'online')->call('place')->assertHasErrors('payment_method');
    expect(Order::count())->toBe(0)->and(Payment::count())->toBe(0)->and($product->fresh()->stock)->toBe(3)
        ->and(app(CartService::class)->count())->toBe(1);
    Notification::assertNothingSent();
    Http::assertNothingSent();
});

it('exige une nouvelle confirmation apres changement des frais et conserve les champs', function (): void {
    configureDelivery('free');
    [$user, $product] = deliveryBasket();
    $page = Volt::actingAs($user)->test('pages.public.checkout')->set('contact_phone', 'Fictif')
        ->set('delivery_address', 'Adresse fictive')->set('payment_method', 'cod');
    configureDelivery('fixed', 250);
    $page->call('place')->assertHasErrors('delivery')->assertSet('delivery_address', 'Adresse fictive')->assertSee('250 FCFA');
    expect(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(3);
    Notification::assertNothingSent();
    $page->call('place')->assertHasNoErrors()->assertRedirect();
    expect(Order::sole()->total)->toBe(1250)->and(Order::sole()->delivery_pricing)->toBe('fixed');
});

it('refuse le paiement devenu incomplet apres ouverture de la page', function (): void {
    configureDelivery('free');
    [$user] = deliveryBasket();
    $page = Volt::actingAs($user)->test('pages.public.checkout')->set('contact_phone', 'Fictif')->set('delivery_address', 'Adresse fictive');
    configureDelivery('unknown');
    $page->call('place')->assertHasErrors('delivery')->assertSee('Total hors livraison');
    $page->call('place')->assertHasErrors('payment_method');
    expect(Order::count())->toBe(0)->and(Payment::count())->toBe(0);
    Notification::assertNothingSent();
});

it('ne change pas une commande connue quand les reglages ulterieurs changent', function (): void {
    configureDelivery('fixed', 250);
    [$user, $product] = deliveryBasket();
    $order = app(OrderService::class)->place($user, deliveryAddress(), PaymentMethod::Online);
    $before = $order->fresh()->getRawOriginal();
    configureDelivery('unknown');
    $payment = app(PaymentService::class)->resumeOrder($order, $user);
    expect($payment->amount)->toBe(1250)->and($order->fresh()->getRawOriginal())->toBe($before)
        ->and($product->fresh()->stock)->toBe(2);
    Http::assertNothingSent();
});

it('initialise le paiement sur le total final des commandes nouvelles', function (string $mode, int $fee, int $total): void {
    configureDelivery($mode, $fee);
    [$user] = deliveryBasket();
    $order = app(OrderService::class)->place($user, deliveryAddress(), PaymentMethod::Online);
    $first = app(PaymentService::class)->open($order, PaymentProvider::Kkiapay);
    $again = app(PaymentService::class)->resumeOrder($order, $user);
    expect($first->amount)->toBe($total)->and($again->id)->toBe($first->id)->and(Payment::count())->toBe(1);
    Http::assertNothingSent();
})->with([['free', 0, 1000], ['fixed', 250, 1250]]);

it('ne rend pas gratuit un ancien zero ni ne reinitialise un ancien paiement', function (): void {
    [$user, $product] = deliveryBasket();
    $order = Order::create(['number' => 'F13-ANCIEN', 'user_id' => $user->id, 'subtotal' => 1000, 'total' => 1000,
        'payment_method' => 'online', 'reservation_expires_at' => now()->addMinutes(15), 'stock_reservation' => [$product->id => 1], ...deliveryAddress()]);
    $payment = $order->payments()->create(['provider' => 'kkiapay', 'reference' => 'F13-ANCIEN-P', 'state' => 'pending', 'amount' => 1000]);
    $before = [$order->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal()];
    configureDelivery('free');
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $order])->assertSee('Montant enregistré')->assertDontSee('Gratuite')
        ->call('resumePayment')->assertHasErrors('payment')->assertNotDispatched('open-order-payment');
    expect([$order->fresh()->getRawOriginal(), $payment->fresh()->getRawOriginal()])->toBe($before);
    Http::assertNothingSent();
});

it('conserve un paiement ancien effectivement recu mais exige sa verification commerciale', function (): void {
    [$user] = deliveryBasket();
    $order = Order::create(['number' => 'F13-RECU', 'user_id' => $user->id, 'subtotal' => 1000, 'total' => 1000,
        'payment_method' => 'online', ...deliveryAddress()]);
    $payment = $order->payments()->create(['provider' => 'kkiapay', 'reference' => 'F13-RECU-P', 'state' => 'pending', 'amount' => 1000, 'currency' => 'XOF']);
    $result = new ChargeResult(state: PaymentState::Succeeded, amount: 1000, providerReference: 'F13-TRANSACTION', currency: 'XOF');
    app(PaymentService::class)->settle($payment, $result);
    app(PaymentService::class)->settle($payment, $result);
    expect($payment->fresh()->state)->toBe(PaymentState::Succeeded)->and($order->fresh()->payment_status->value)->toBe('paid')
        ->and($order->fresh()->payment_review_required)->toBeTrue()->and($order->fresh()->status->value)->toBe('pending')
        ->and($order->fresh()->total)->toBe(1000)->and($payment->events()->where('type', 'delivery_review_required')->count())->toBe(1);
});

it('ajoute les metadonnees sans reecrire les montants et etats historiques', function (): void {
    [$user] = deliveryBasket();
    $order = Order::create(['number' => 'F13-MIGRATION', 'user_id' => $user->id, 'subtotal' => 1000, 'delivery_fee' => 250, 'total' => 1250,
        'payment_status' => 'paid', ...deliveryAddress()]);
    $before = Arr::except($order->fresh()->getRawOriginal(), ['delivery_pricing']);
    $migration = require database_path('migrations/2026_10_27_030100_add_delivery_pricing_to_orders.php');
    $migration->down();
    $migration->up();
    expect(Arr::except($order->fresh()->getRawOriginal(), ['delivery_pricing']))->toBe($before)
        ->and($order->fresh()->delivery_pricing)->toBeNull()->and($order->fresh()->deliveryIsFinal())->toBeFalse();
});

it('initialise le mode uniquement selon un tarif historique positif sans toucher au tarif', function (int $fee, string $mode): void {
    $shop = app(ShopSettings::class);
    $shop->default_delivery_fee = $fee;
    $shop->save();
    $before = DB::table('settings')->where('group', 'shop')->where('name', 'default_delivery_fee')->first();
    $migration = require database_path('settings/2026_10_27_030000_add_delivery_pricing_mode.php');
    $migration->down();
    $migration->up();
    expect(app(SettingsRepository::class)->getPropertyPayload('delivery', 'mode'))->toBe($mode)
        ->and(DB::table('settings')->where('group', 'shop')->where('name', 'default_delivery_fee')->first())->toEqual($before);
})->with([[0, 'unknown'], [250, 'fixed']]);

it('reserve les reglages de livraison aux comptes autorises', function (): void {
    [$client] = deliveryBasket();
    Volt::actingAs($client)->test('pages.admin.reglages.delivery')->assertForbidden();
    $staff = User::factory()->create();
    $staff->assignRole(Role::SuperAdmin->value);
    Volt::actingAs($staff)->test('pages.admin.reglages.delivery')->set('mode', 'fixed')->set('fee', '250')->call('save')->assertHasNoErrors();
    expect(app(ShopSettings::class)->deliveryFee())->toBe(250)->and(app(DeliverySettings::class)->mode)->toBe('fixed');
});

it('refuse les tarifs fixes invalides sans modifier les reglages', function (string $fee): void {
    $staff = User::factory()->create();
    $staff->assignRole(Role::SuperAdmin->value);
    Volt::actingAs($staff)->test('pages.admin.reglages.delivery')->set('mode', 'fixed')->set('fee', $fee)->call('save')->assertHasErrors('fee');
    expect(app(DeliverySettings::class)->mode)->toBe('unknown')->and(app(ShopSettings::class)->default_delivery_fee)->toBe(0);
})->with(['0', '-1', '1.5', 'invalide']);

it('permet de choisir la gratuite explicite apres un tarif invalide', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole(Role::SuperAdmin->value);
    $page = Volt::actingAs($staff)->test('pages.admin.reglages.delivery')->set('mode', 'fixed')->set('fee', '-1')->call('save')->assertHasErrors('fee');
    $page->set('mode', 'free')->call('save')->assertHasNoErrors();
    expect(app(ShopSettings::class)->deliveryPricing())->toBe(DeliveryPricing::Free)
        ->and(app(ShopSettings::class)->default_delivery_fee)->toBe(0);
});

it('explique labsence de mode disponible lorsque seuls les paiements en ligne sont configures', function (): void {
    app(PaymentSettings::class)->mode = 'online';
    [$user] = deliveryBasket();
    Volt::actingAs($user)->test('pages.public.checkout')->assertSee('Aucun mode de paiement n’est disponible')
        ->assertSee('Contactez notre équipe')->set('contact_phone', 'Fictif')->set('delivery_address', 'Fictive')
        ->call('place')->assertHasErrors('payment_method');
    expect(Order::count())->toBe(0)->and(Payment::count())->toBe(0);
    Notification::assertNothingSent();
});

function deliveryBasket(): array
{
    $user = User::factory()->create(['name' => 'Client fictif']);
    $user->assignRole(Role::Client->value);
    $product = Product::create(['name' => 'Article fictif', 'slug' => 'livraison-'.bin2hex(random_bytes(6)), 'price' => 1000, 'stock' => 3, 'is_active' => true]);
    app(CartService::class)->add($product->id);

    return [$user, $product];
}

function deliveryAddress(): array
{
    return ['contact_name' => 'Client fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Adresse fictive'];
}

it('F13 annonce un total hors livraison quand les frais restent inconnus', function (): void {
    deliveryBasket();
    $html = Volt::test('pages.public.cart')->html();
    expect(str_contains($html, 'Total hors livraison'))->toBeTrue();
});

it('F13 refuse une commande en ligne dont la livraison nest pas determinee', function (): void {
    [$user, $product] = deliveryBasket();
    expect(fn () => app(OrderService::class)->place($user, deliveryAddress(), PaymentMethod::Online))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0)->and(Payment::count())->toBe(0)->and($product->fresh()->stock)->toBe(3)
        ->and(app(CartService::class)->count())->toBe(1);
    Notification::assertNothingSent();
    Http::assertNothingSent();
});

it('F13 refuse louverture directe dun paiement sur une commande historique ambigue', function (): void {
    [$user] = deliveryBasket();
    $order = Order::create(['number' => 'F13-HISTORIQUE', 'user_id' => $user->id, 'subtotal' => 1000, 'delivery_fee' => 0, 'total' => 1000, 'payment_method' => 'online', ...deliveryAddress()]);
    expect(fn () => app(PaymentService::class)->open($order, PaymentProvider::Kkiapay))->toThrow(ValidationException::class);
    expect(Payment::count())->toBe(0);
    Http::assertNothingSent();
});
