<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('ajoute les réservations sans réécrire les anciennes commandes ni leurs paiements', function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    $migration = require database_path('migrations/2026_10_26_000000_add_stock_reservation_to_orders.php');
    $migration->down();
    $user = User::factory()->create();
    $id = DB::table('orders')->insertGetId([
        'number' => 'FICTIF-HISTORIQUE', 'user_id' => $user->id, 'status' => 'pending',
        'payment_method' => 'online', 'payment_status' => 'unpaid', 'subtotal' => 1000,
        'total' => 1000, 'contact_name' => 'Fictif', 'contact_phone' => 'Fictif',
        'delivery_address' => 'Fictive', 'created_at' => now()->subYear(), 'updated_at' => now()->subYear(),
    ]);
    $paymentId = DB::table('payments')->insertGetId([
        'payable_type' => 'App\\Models\\Order', 'payable_id' => $id, 'provider' => 'kkiapay',
        'reference' => 'FICTIF-HISTORIQUE', 'amount' => 1000, 'currency' => 'XOF', 'state' => 'pending',
    ]);
    $before = (array) DB::table('orders')->find($id);
    $paymentBefore = DB::table('payments')->find($paymentId);
    $migration->up();
    $after = (array) DB::table('orders')->find($id);
    expect(array_intersect_key($after, $before))->toEqual($before)
        ->and($after['reservation_expires_at'])->toBeNull()
        ->and($after['reservation_released_at'])->toBeNull()
        ->and($after['stock_reservation'])->toBeNull()
        ->and((bool) $after['payment_review_required'])->toBeFalse()
        ->and($after['checkout_token'])->toBeNull();
    $this->artisan('orders:expire-reservations')->assertSuccessful();
    expect((array) DB::table('orders')->find($id))->toEqual($after)
        ->and(DB::table('payments')->find($paymentId))->toEqual($paymentBefore);
    Notification::assertNothingSent();
});
