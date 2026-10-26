<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    config(['orders.number_sequence_floors' => null]);
    $this->numberMigration = require database_path('migrations/2026_10_26_010000_create_order_number_sequences_table.php');
    $this->numberMigration->down();
});

function migrationNumberOrder(string $number): int
{
    return DB::table('orders')->insertGetId([
        'number' => $number, 'user_id' => User::factory()->create()->id,
        'status' => 'pending', 'payment_method' => 'online', 'payment_status' => 'unpaid',
        'subtotal' => 1000, 'total' => 1000, 'contact_name' => 'Fictif', 'contact_phone' => 'Fictif',
        'delivery_address' => 'Fictive', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
    ]);
}

it('initialise une installation vide sans configuration', function (): void {
    $this->numberMigration->up();
    expect(Schema::hasTable('order_number_sequences'))->toBeTrue()
        ->and(DB::table('order_number_sequences')->count())->toBe(0);
});

it('conserve les commandes et paiements historiques lors de l’initialisation et du rollback de schéma', function (): void {
    $id = migrationNumberOrder('TKM-CMD-2026-05000');
    $snapshot = DB::table('orders')->find($id);
    $paymentId = DB::table('payments')->insertGetId([
        'payable_type' => 'App\\Models\\Order', 'payable_id' => $id, 'provider' => 'kkiapay',
        'reference' => 'FICTIF-HISTORIQUE', 'amount' => 1000, 'currency' => 'XOF', 'state' => 'pending',
    ]);
    $payment = DB::table('payments')->find($paymentId);
    config(['orders.number_sequence_floors' => '{"2026":6000,"2025":42}']);
    $this->numberMigration->up();
    expect(DB::table('orders')->find($id))->toEqual($snapshot)
        ->and(DB::table('payments')->find($paymentId))->toEqual($payment)
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(6000)
        ->and(DB::table('order_number_sequences')->where('year', 2025)->value('last_number'))->toBe(42);
    $this->numberMigration->down();
    expect(DB::table('orders')->find($id))->toEqual($snapshot)
        ->and(DB::table('payments')->find($paymentId))->toEqual($payment);
});

it('refuse un historique sans plafond vérifié ou avec un plafond inférieur sans créer la table', function (?string $floors): void {
    $id = migrationNumberOrder('TKM-CMD-2026-04200');
    config(['orders.number_sequence_floors' => $floors]);
    expect(fn () => $this->numberMigration->up())->toThrow(RuntimeException::class, 'Initialisation requise');
    expect(Schema::hasTable('order_number_sequences'))->toBeFalse()
        ->and(DB::table('orders')->find($id)->number)->toBe('TKM-CMD-2026-04200');
})->with([null, '{}', '{"2026":4199}', '{"2025":4200}']);

it('refuse une configuration invalide avant tout changement de schéma', function (mixed $floors): void {
    config(['orders.number_sequence_floors' => $floors]);
    expect(fn () => $this->numberMigration->up())->toThrow(RuntimeException::class);
    expect(Schema::hasTable('order_number_sequences'))->toBeFalse();
})->with(['{', '[]', 'null', 'true', '{"2026":-1}', '{"2026":1.5}', '{"2026":"2"}', '{"2026":true}', '{"26":1}', '{"0000":1}', '{"2026":9999999999999999999999999}', 100]);

it('refuse les numéros historiques incompatibles sans les réécrire', function (string $number): void {
    $id = migrationNumberOrder($number);
    config(['orders.number_sequence_floors' => '{"2026":99999}']);
    expect(fn () => $this->numberMigration->up())->toThrow(RuntimeException::class);
    expect(Schema::hasTable('order_number_sequences'))->toBeFalse()
        ->and(DB::table('orders')->find($id)->number)->toBe($number);
})->with(['TKM-CMD-2026-1', 'TKM-CMD-2026-00000', 'TKM-CMD-2026-ABCDE', 'TKM-CMD-0000-00001', 'TKM-CMD-2026-999999999999999999999999']);

it('préserve les variantes de casse et de remplissage numériques des anciennes références', function (): void {
    $id = migrationNumberOrder('tkm-cmd-2026-000042');
    config(['orders.number_sequence_floors' => '{"2026":42}']);
    $this->numberMigration->up();
    expect(DB::table('orders')->find($id)->number)->toBe('tkm-cmd-2026-000042')
        ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe(42);
});
