<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
    $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
});

function numberingBuyer(): User
{
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole(Role::Client->value);

    return $user;
}

function historicalNumberedOrder(string $number, User $user): Order
{
    return Order::create([
        'number' => $number, 'user_id' => $user->id, 'status' => 'pending',
        'payment_method' => 'cod', 'payment_status' => 'unpaid', 'subtotal' => 1000,
        'total' => 1000, 'contact_name' => 'Client fictif', 'contact_phone' => 'Fictif',
        'delivery_address' => 'Adresse fictive',
    ]);
}

function placeNumberedOrder(User $user, ?string $token = null): Order
{
    $product = Product::create([
        'name' => 'Article fictif', 'slug' => 'numbering-'.bin2hex(random_bytes(5)),
        'price' => 1000, 'stock' => 2, 'is_active' => true,
    ]);
    app(CartService::class)->add($product->id);

    return app(OrderService::class)->place($user, [
        'contact_name' => 'Client fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Adresse fictive',
    ], checkoutToken: $token);
}

function initializeOrderNumbers(string $floors): void
{
    $migration = require database_path('migrations/2026_10_26_010000_create_order_number_sequences_table.php');
    $migration->down();
    config(['orders.number_sequence_floors' => $floors]);
    $migration->up();
}

it('ne réutilise pas un numéro existant lorsque la numérotation historique comporte un trou', function (): void {
    $user = numberingBuyer();
    historicalNumberedOrder('TKM-CMD-2026-00001', $user);
    historicalNumberedOrder('TKM-CMD-2026-00003', $user);
    initializeOrderNumbers('{"2026":3}');

    expect(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2026-00004');
});

it('attribue des numéros distincts au format actuel', function (): void {
    $first = placeNumberedOrder(numberingBuyer());
    $second = placeNumberedOrder(numberingBuyer());
    expect($first->number)->toBe('TKM-CMD-2026-00001')
        ->and($second->number)->toBe('TKM-CMD-2026-00002')
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(2);
});

it('ne diminue pas le compteur après suppression des dernières commandes', function (): void {
    $user = numberingBuyer();
    $first = placeNumberedOrder($user);
    $second = placeNumberedOrder($user);
    $first->delete();
    $second->delete();
    expect(Order::count())->toBe(0)->and(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2026-00003');
});

it('demonstration 08 : conserve l’historique et poursuit après une suppression', function (): void {
    $user = numberingBuyer();
    $first = historicalNumberedOrder('TKM-CMD-2026-00001', $user);
    $third = historicalNumberedOrder('TKM-CMD-2026-00003', $user);
    $snapshots = [$first->fresh()->getRawOriginal(), $third->fresh()->getRawOriginal()];
    initializeOrderNumbers('{"2026":10}'); // Inclut des numéros supprimés connus de l'opérateur fictif.
    $eleventh = placeNumberedOrder($user);
    $twelfth = placeNumberedOrder($user);
    $twelfth->delete();
    $thirteenth = placeNumberedOrder($user);
    expect($eleventh->number)->toBe('TKM-CMD-2026-00011')
        ->and($twelfth->number)->toBe('TKM-CMD-2026-00012')
        ->and($thirteenth->number)->toBe('TKM-CMD-2026-00013')
        ->and($first->fresh()->getRawOriginal())->toEqual($snapshots[0])
        ->and($third->fresh()->getRawOriginal())->toEqual($snapshots[1])
        ->and(Order::count())->toBe(4)
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(13);
    Notification::assertCount(3);
});

it('initialise depuis le numéro historique élevé et non depuis created_at', function (): void {
    $user = numberingBuyer();
    $historical = historicalNumberedOrder('TKM-CMD-2026-04200', $user);
    $historical->update(['created_at' => '2021-01-01 00:00:00']);
    $snapshot = $historical->fresh()->getRawOriginal();
    initializeOrderNumbers('{"2026":4200}');
    expect(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2026-04201')
        ->and($historical->fresh()->getRawOriginal())->toEqual($snapshot);
    Volt::actingAs($user)->test('pages.app.orders-show', ['order' => $historical])->assertSee($historical->number);
    $this->actingAs($user)->get(route('app.orders.show', $historical->number))->assertOk();
});

it('respecte le plafond vérifié incluant les commandes déjà supprimées', function (): void {
    $user = numberingBuyer();
    historicalNumberedOrder('TKM-CMD-2026-00002', $user);
    initializeOrderNumbers('{"2026":12000,"2027":900}');
    expect(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2026-12001');
    $this->travelTo(now()->setDate(2027, 1, 1));
    expect(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2027-00901');
});

it('change d’année dans le fuseau applicatif avec une séquence indépendante', function (): void {
    config(['app.timezone' => 'Africa/Lagos']);
    $user = numberingBuyer();
    $this->travelTo(Carbon::parse('2026-12-31 22:59:59', 'UTC'));
    $first = placeNumberedOrder($user);
    $this->travelTo(Carbon::parse('2026-12-31 23:00:00', 'UTC'));
    $second = placeNumberedOrder($user);
    expect($first->number)->toBe('TKM-CMD-2026-00001')
        ->and($second->number)->toBe('TKM-CMD-2027-00001')
        ->and($first->fresh()->number)->toBe('TKM-CMD-2026-00001');
});

it('dépasse cinq chiffres sans remise à zéro ni troncature', function (): void {
    initializeOrderNumbers('{"2026":99999}');
    expect(placeNumberedOrder(numberingBuyer())->number)->toBe('TKM-CMD-2026-100000');
});

it('conserve les références historiques d’un autre format sans les modifier', function (): void {
    $user = numberingBuyer();
    $old = historicalNumberedOrder('SZ-CMD-2026-12345', $user);
    initializeOrderNumbers('{}');
    expect(placeNumberedOrder($user)->number)->toBe('TKM-CMD-2026-00001')
        ->and($old->fresh()->number)->toBe('SZ-CMD-2026-12345');
});

it('ne consomme pas un autre numéro lors du rejeu du checkout', function (): void {
    $user = numberingBuyer();
    $token = (string) Str::uuid();
    $first = placeNumberedOrder($user, $token);
    $second = app(OrderService::class)->place($user, [
        'contact_name' => 'Fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Fictive',
    ], checkoutToken: $token);
    expect($second->id)->toBe($first->id)->and(Order::count())->toBe(1)
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(1);
    Notification::assertCount(1);
});

it('annule aussi l’allocation du numéro lors du rollback métier', function (): void {
    $user = numberingBuyer();
    placeNumberedOrder($user);
    Event::listen('eloquent.updated: '.Product::class, function (): void {
        throw new RuntimeException('Écriture fictive refusée');
    });
    expect(fn () => placeNumberedOrder($user))->toThrow(RuntimeException::class, 'Écriture fictive refusée');
    expect(Order::count())->toBe(1)
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(1)
        ->and(app(CartService::class)->count())->toBe(1);
    Event::forget('eloquent.updated: '.Product::class);
    $retried = app(OrderService::class)->place($user, [
        'contact_name' => 'Fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Fictive',
    ]);
    expect($retried->number)->toBe('TKM-CMD-2026-00002');
    Notification::assertCount(2);
});

it('refuse un compteur absent ou épuisé sans commande ni panier perdu', function (int $last): void {
    DB::table('order_number_sequences')->insert(['year' => 2026, 'last_number' => $last]);
    expect(fn () => placeNumberedOrder(numberingBuyer()))->toThrow(RuntimeException::class, 'Compteur annuel absent ou épuisé');
    expect(Order::count())->toBe(0)->and(app(CartService::class)->count())->toBe(1)
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe($last);
    Notification::assertNothingSent();
})->with([PHP_INT_MAX, -1]);

it('refuse de deviner un compteur perdu devant des numéros historiques', function (): void {
    $user = numberingBuyer();
    historicalNumberedOrder('TKM-CMD-2026-00010', $user);
    expect(fn () => placeNumberedOrder($user))->toThrow(RuntimeException::class, 'Compteur annuel non initialisé');
    expect(Order::count())->toBe(1);
});
