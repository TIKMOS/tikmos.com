<?php

declare(strict_types=1);

use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Base dédiée : rejouer la migration de l'existant sans toucher la base de l'application.
beforeEach(function (): void {
    $this->originalConnection = DB::getDefaultConnection();
    config(['database.connections.payment_migration_test' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('payment_migration_test');
    $originalMigration = require database_path('migrations/2026_07_15_000100_create_payments_tables.php');
    $originalMigration->up();
    $this->referenceMigration = require database_path('migrations/2026_10_24_000000_add_unique_provider_reference_to_payments.php');
});

afterEach(function (): void {
    DB::purge('payment_migration_test');
    DB::setDefaultConnection($this->originalConnection);
});

function historicalPayment(string $reference, ?string $externalReference, string $provider = 'kkiapay'): array
{
    return [
        'payable_type' => Order::class,
        'payable_id' => 1,
        'provider' => $provider,
        'reference' => $reference,
        'provider_reference' => $externalReference,
        'amount' => 16000,
        'currency' => 'XOF',
        'state' => 'pending',
    ];
}

it('ajoute la contrainte sans modifier les paiements existants et autorise les références nulles', function (): void {
    DB::table('payments')->insert([
        historicalPayment('LOCAL-1', 'EXTERNAL-1'),
        historicalPayment('LOCAL-2', null),
        historicalPayment('LOCAL-3', null),
        historicalPayment('LOCAL-4', 'EXTERNAL-1', 'other-provider'),
    ]);
    $before = DB::table('payments')->orderBy('id')->get()->toJson();
    $this->referenceMigration->up();

    expect(DB::table('payments')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(Schema::hasIndex('payments', 'payments_provider_reference_unique', 'unique'))->toBeTrue();
    DB::table('payments')->insert(historicalPayment('LOCAL-5', null));
    DB::table('payments')->insert(historicalPayment('LOCAL-6', 'EXTERNAL-1', 'third-provider'));
    expect(fn () => DB::table('payments')->insert(historicalPayment('LOCAL-7', 'EXTERNAL-1')))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('interrompt la migration sur des doublons historiques sans les supprimer ni les réaffecter', function (): void {
    $second = historicalPayment('LOCAL-2', 'DUPLICATE');
    $second['state'] = 'failed';
    $second['payable_id'] = 2;
    DB::table('payments')->insert([historicalPayment('LOCAL-1', 'DUPLICATE'), $second]);
    $before = DB::table('payments')->orderBy('id')->get()->toJson();

    expect(fn () => $this->referenceMigration->up())
        ->toThrow(RuntimeException::class, 'doublons fournisseur/référence externe');
    expect(DB::table('payments')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(Schema::hasIndex('payments', 'payments_provider_reference_unique'))->toBeFalse();
});

it('retire seulement la contrainte lors du rollback de la nouvelle migration', function (): void {
    DB::table('payments')->insert(historicalPayment('LOCAL-1', 'EXTERNAL-1'));
    $this->referenceMigration->up();
    $before = DB::table('payments')->get()->toJson();
    $this->referenceMigration->down();

    expect(DB::table('payments')->get()->toJson())->toBe($before)
        ->and(Schema::hasIndex('payments', 'payments_provider_reference_unique'))->toBeFalse()
        ->and(Schema::hasIndex('payments', 'payments_provider_reference_index'))->toBeTrue();
    DB::table('payments')->insert(historicalPayment('LOCAL-2', 'EXTERNAL-1'));
    expect(DB::table('payments')->count())->toBe(2);
});
