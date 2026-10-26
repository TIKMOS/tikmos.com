<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Payments\Data\ChargeResult;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Enums\PaymentState;
use App\Services\CartService;
use App\Services\PaymentService;
use App\Settings\PaymentSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();

    $this->seed(RolePermissionSeeder::class);

    // Clés KKiaPay factices (les appels réseau sont mockés via Http::fake).
    config([
        'payments.gateways.kkiapay.public_key' => 'pk_test',
        'payments.gateways.kkiapay.private_key' => 'sk_test',
        'payments.gateways.kkiapay.secret' => 'whsec_test',
        'payments.gateways.kkiapay.sandbox' => true,
    ]);
});

it('refuse de payer deux commandes avec la même transaction externe', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $service = app(PaymentService::class);
    $firstOrder = payOrder();
    $secondOrder = payOrder();
    $firstPayment = $service->open($firstOrder, PaymentProvider::Kkiapay);
    $secondPayment = $service->open($secondOrder, PaymentProvider::Kkiapay);

    expect($service->verify($firstPayment, 'SAME-EXTERNAL-TXN'))->toBeTrue();
    $secondAccepted = $service->verify($secondPayment, 'SAME-EXTERNAL-TXN');

    expect($firstOrder->fresh()->payment_status->value)->toBe('paid')
        ->and($secondOrder->fresh()->payment_status->value)->toBe('unpaid')
        ->and($secondAccepted)->toBeFalse()
        ->and($secondPayment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($secondPayment->fresh()->provider_reference)->toBeNull()
        ->and($secondPayment->fresh()->paid_at)->toBeNull()
        ->and($secondPayment->events()->where('type', 'succeeded')->count())->toBe(0)
        ->and($secondPayment->events()->where('type', 'provider_reference_conflict')->count())->toBe(1)
        ->and(Payment::where('provider', 'kkiapay')->where('provider_reference', 'SAME-EXTERNAL-TXN')->count())->toBe(1);
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
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', 'failed')->count())->toBe(1)
        ->and($payment->events()->where('type', 'amount_missing')->count())->toBe(0);
});

it('refuse un succès KKiaPay sans montant confirmé', function (): void {
    fakeKkiapay('SUCCESS', null);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    $accepted = $service->verify($payment, 'TXN-NO-AMOUNT');

    expect($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($accepted)->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($payment->fresh()->provider_reference)->toBeNull()
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($payment->events()->where('type', 'amount_missing')->count())->toBe(1)
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(0);
});

it('refuse les montants KKiaPay invalides sans conversion silencieuse', function (mixed $amount, int $total): void {
    Http::fake(['*/api/v1/transactions/status' => Http::response(['status' => 'SUCCESS', 'amount' => $amount])]);
    $order = payOrder($total);
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->verify($payment, 'TXN-INVALID-AMOUNT'))->toBeFalse()
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($payment->fresh()->provider_reference)->toBeNull()
        ->and($payment->events()->where('type', 'amount_invalid')->count())->toBe(1);
})->with([
    'texte' => ['not-an-amount', 0],
    'chaîne vide' => ['', 0],
    'suffixe non numérique' => ['16000XOF', 16000],
    'fraction textuelle' => ['16000.9', 16000],
    'fraction numérique' => [16000.9, 16000],
    'booléen vrai' => [true, 1],
    'booléen faux' => [false, 0],
    'tableau vide' => [[], 0],
    'objet' => [['value' => 16000], 1],
    'montant négatif' => [-1, 0],
    'notation exponentielle textuelle' => ['1.6e4', 16000],
    'dépassement entier' => ['9223372036854775808', 16000],
]);

it('accepte un montant entier confirmé sans perte', function (int|float|string $amount): void {
    Http::fake(['*/api/v1/transactions/status' => Http::response(
        json_encode(['status' => 'SUCCESS', 'amount' => $amount, 'currency' => 'XOF'], JSON_PRESERVE_ZERO_FRACTION),
        200,
        ['Content-Type' => 'application/json'],
    )]);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->verify($payment, 'TXN-VALID-AMOUNT'))->toBeTrue()
        ->and($order->fresh()->payment_status->value)->toBe('paid')
        ->and($payment->fresh()->amount)->toBe(16000);
})->with([16000, '16000', 16000.0]);

it('compare zéro au montant dû sans le confondre avec une absence', function (int $total, int $amount, bool $accepted): void {
    fakeKkiapay('SUCCESS', $amount);
    $order = payOrder($total);
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->verify($payment, 'TXN-ZERO'))->toBe($accepted)
        ->and($order->fresh()->payment_status->value)->toBe($accepted ? 'paid' : 'unpaid')
        ->and($payment->events()->where('type', 'amount_missing')->count())->toBe(0)
        ->and($payment->events()->where('type', 'amount_mismatch')->count())->toBe($accepted ? 0 : 1);
})->with([
    'zéro confirmé pour zéro dû' => [0, 0, true],
    'zéro insuffisant' => [16000, 0, false],
    'montant supérieur au dû' => [0, 1, false],
]);

it('protège aussi le règlement direct contre un montant non confirmé', function (?int $amount, string $event): void {
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->settle($payment, new ChargeResult(
        state: PaymentState::Succeeded,
        providerReference: 'TXN-DIRECT',
        amount: $amount,
    )))->toBeFalse()
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', $event)->count())->toBe(1);
})->with(['absent' => [null, 'amount_missing'], 'négatif' => [-1, 'amount_invalid']]);

it('préserve la confirmation interne du paiement à la livraison sans montant de passerelle', function (): void {
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Cod);

    expect($service->settle($payment, new ChargeResult(
        state: PaymentState::Succeeded,
        method: 'cash',
    )))->toBeTrue()
        ->and($payment->fresh()->state)->toBe(PaymentState::Succeeded)
        ->and($order->fresh()->payment_status->value)->toBe('paid');
    Http::assertNothingSent();
});

it('refuse une devise fournie invalide ou différente du paiement local', function (mixed $currency, string $event): void {
    Http::fake(['*/api/v1/transactions/status' => Http::response([
        'status' => 'SUCCESS', 'amount' => 16000, 'currency' => $currency,
    ])]);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->verify($payment, 'TXN-CURRENCY'))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', $event)->count())->toBe(1);
})->with([
    'autre devise' => ['EUR', 'currency_mismatch'],
    'code en minuscules' => ['xof', 'currency_invalid'],
    'devise nulle' => [null, 'currency_invalid'],
    'devise vide' => ['', 'currency_invalid'],
    'code numérique' => [952, 'currency_invalid'],
    'devise structurée' => [['code' => 'XOF'], 'currency_invalid'],
]);

it('ne suppose pas une autre devise locale lorsque KKiaPay ne la fournit pas', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    $payment->update(['currency' => 'EUR']);

    expect($service->verify($payment, 'TXN-UNSUPPORTED-CURRENCY'))->toBeFalse()
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', 'currency_unsupported')->count())->toBe(1);
});

it('conserve une vérification incomplète ou en attente sans la transformer en échec de paiement', function (mixed $body, int $httpStatus, string $event): void {
    Http::fake(['*/api/v1/transactions/status' => Http::response($body, $httpStatus)]);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->verify($payment, 'TXN-INCOMPLETE'))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', $event)->count())->toBe(1)
        ->and($payment->events()->where('type', 'failed')->count())->toBe(0);
})->with([
    'HTTP non réussi' => [['status' => 'SUCCESS', 'amount' => 16000], 503, 'verification_unavailable'],
    'JSON invalide' => ['{invalid-json', 200, 'verification_incomplete'],
    'JSON scalaire' => ['"SUCCESS"', 200, 'verification_incomplete'],
    'statut absent' => [['amount' => 16000], 200, 'verification_incomplete'],
    'statut mal formé' => [['status' => ['SUCCESS']], 200, 'verification_incomplete'],
    'montant explicitement nul' => [['status' => 'SUCCESS', 'amount' => null], 200, 'amount_missing'],
    'transaction en attente' => [['status' => 'PENDING'], 200, 'verification_pending'],
]);

it('utilise exclusivement le montant revérifié côté serveur après le webhook', function (): void {
    Http::fake(['*/api/v1/transactions/status' => Http::sequence()
        ->push(['status' => 'SUCCESS'])
        ->push(['status' => 'SUCCESS', 'amount' => 16000])]);
    $order = payOrder();
    $payment = app(PaymentService::class)->open($order, PaymentProvider::Kkiapay);
    $payload = ['transactionId' => 'TXN-SERVER-ONLY', 'data' => json_encode(['reference' => $payment->reference])];

    $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', $payload + ['amount' => 16000, 'currency' => 'XOF'])
        ->assertOk()->assertJson(['handled' => false]);
    expect($order->fresh()->payment_status->value)->toBe('unpaid');

    $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', $payload + ['amount' => 1, 'currency' => 'EUR'])
        ->assertOk()->assertJson(['handled' => true]);
    expect($order->fresh()->payment_status->value)->toBe('paid')
        ->and($payment->fresh()->amount)->toBe(16000)
        ->and($payment->events()->where('type', 'amount_missing')->count())->toBe(1)
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(1);
});

it('ne journalise pas les secrets ni les données client retournés par la passerelle', function (string $status, mixed $amount): void {
    Http::fake(['*/api/v1/transactions/status' => Http::response([
        'status' => $status,
        'amount' => $amount,
        'private_key' => 'DO-NOT-LOG-PRIVATE',
        'token' => 'DO-NOT-LOG-TOKEN',
        'client' => ['email' => 'private@example.test'],
        'source' => ['secret' => 'DO-NOT-LOG-SOURCE'],
    ])]);
    $service = app(PaymentService::class);
    $payment = $service->open(payOrder(), PaymentProvider::Kkiapay);
    $service->verify($payment, 'TXN-AUDIT-SAFE');

    expect($payment->events()->get()->toJson())->not->toContain('DO-NOT-LOG', 'private@example.test', 'private_key')
        ->and($payment->fresh()->method)->toBeNull();
})->with([
    'succès' => ['SUCCESS', 16000],
    'montant invalide contenant un secret' => ['SUCCESS', 'DO-NOT-LOG-AMOUNT'],
    'échec' => ['FAILED', null],
]);

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

it('ne rejoue pas les effets métier entre widget et webhook', function (bool $webhookFirst): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    $transitions = 0;
    Event::listen('eloquent.updated: '.Order::class, function () use (&$transitions): void {
        $transitions++;
    });
    $webhook = fn () => $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', [
            'transactionId' => 'TXN-REPLAY',
            'data' => json_encode(['reference' => $payment->reference]),
        ])->assertOk();

    if ($webhookFirst) {
        $webhook()->assertJson(['handled' => true]);
    } else {
        expect($service->verify($payment, 'TXN-REPLAY'))->toBeTrue();
    }

    $paidAt = $payment->fresh()->paid_at->toISOString();
    $confirmedAt = $order->fresh()->confirmed_at->toISOString();
    $this->travel(1)->minutes();
    $webhook()->assertJson(['handled' => false]);
    expect($service->verify($payment, 'TXN-REPLAY'))->toBeFalse()
        ->and($transitions)->toBe(1)
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(1)
        ->and($payment->fresh()->provider_reference)->toBe('TXN-REPLAY')
        ->and($payment->fresh()->paid_at->toISOString())->toBe($paidAt)
        ->and($order->fresh()->confirmed_at->toISOString())->toBe($confirmedAt);
    Notification::assertNothingSent();
})->with(['widget puis webhook' => false, 'webhook puis widget' => true]);

it('refuse la transaction déjà utilisée reçue par webhook puis accepte une autre transaction', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $service = app(PaymentService::class);
    $firstPayment = $service->open(payOrder(), PaymentProvider::Kkiapay);
    $secondOrder = payOrder();
    $secondPayment = $service->open($secondOrder, PaymentProvider::Kkiapay);
    expect($service->verify($firstPayment, 'TXN-USED'))->toBeTrue();

    $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', [
            'transactionId' => 'TXN-USED',
            'data' => json_encode(['reference' => $secondPayment->reference]),
        ])->assertOk()->assertJson(['handled' => false]);

    expect($secondOrder->fresh()->payment_status->value)->toBe('unpaid')
        ->and($secondPayment->events()->where('type', 'provider_reference_conflict')->count())->toBe(1)
        ->and($service->verify($secondPayment, 'TXN-OTHER'))->toBeTrue()
        ->and($secondPayment->fresh()->provider_reference)->toBe('TXN-OTHER')
        ->and($secondOrder->fresh()->payment_status->value)->toBe('paid');
});

it('refuse un webhook dont le fournisseur ne correspond pas au paiement local', function (): void {
    $order = payOrder();
    $payment = app(PaymentService::class)->open($order, PaymentProvider::Cod);

    $this->withHeaders(['x-kkiapay-secret' => 'whsec_test'])
        ->postJson('/webhooks/payments/kkiapay', [
            'transactionId' => 'TXN-WRONG-PROVIDER',
            'data' => json_encode(['reference' => $payment->reference]),
        ])->assertOk()->assertJson(['handled' => false]);

    Http::assertNothingSent();
    expect($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', 'provider_mismatch')->count())->toBe(1);
});

it('ne remplace pas une référence externe déjà rattachée au paiement local', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    $payment->update(['provider_reference' => 'TXN-EXPECTED']);

    expect($service->verify($payment, 'TXN-UNEXPECTED'))->toBeFalse()
        ->and($payment->fresh()->provider_reference)->toBe('TXN-EXPECTED')
        ->and($payment->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', 'provider_reference_mismatch')->count())->toBe(1);
});

it('refuse un succès en ligne sans identité de transaction externe', function (?string $reference): void {
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);

    expect($service->settle($payment, new ChargeResult(
        state: PaymentState::Succeeded,
        providerReference: $reference,
        amount: 16000,
    )))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid')
        ->and($payment->events()->where('type', 'provider_reference_missing')->count())->toBe(1);
})->with([null, '', '   ']);

it('annule le paiement et sa référence si la réaction métier échoue', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    Event::listen('eloquent.updating: '.Order::class, function (): void {
        throw new RuntimeException('Échec métier simulé');
    });

    expect(fn () => $service->verify($payment, 'TXN-ROLLBACK'))
        ->toThrow(RuntimeException::class, 'Échec métier simulé');
    expect($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($payment->fresh()->provider_reference)->toBeNull()
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(0)
        ->and($order->fresh()->payment_status->value)->toBe('unpaid');
});

it('préserve un succès face à un retour échoué utilisant une instance périmée', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    $stalePayment = $payment->fresh();
    expect($service->verify($payment, 'TXN-FINAL'))->toBeTrue();

    expect($service->settle($stalePayment, new ChargeResult(
        state: PaymentState::Failed,
        providerReference: 'TXN-FINAL',
    )))->toBeFalse()
        ->and($payment->fresh()->state)->toBe(PaymentState::Succeeded)
        ->and($payment->fresh()->provider_reference)->toBe('TXN-FINAL')
        ->and($payment->events()->where('type', 'succeeded')->count())->toBe(1)
        ->and($payment->events()->where('type', 'failed')->count())->toBe(0)
        ->and($order->fresh()->payment_status->value)->toBe('paid');
});

it('ne masque pas une autre violation de contrainte unique', function (): void {
    fakeKkiapay('SUCCESS', 16000);
    $order = payOrder();
    $service = app(PaymentService::class);
    $payment = $service->open($order, PaymentProvider::Kkiapay);
    Event::listen('eloquent.updating: '.Order::class, function () use ($order): void {
        DB::table('orders')->insert($order->getRawOriginal());
    });

    expect(fn () => $service->verify($payment, 'TXN-UNRELATED'))
        ->toThrow(UniqueConstraintViolationException::class);
    expect($payment->fresh()->state)->toBe(PaymentState::Pending)
        ->and($payment->events()->where('type', 'provider_reference_conflict')->count())->toBe(0);
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

it('conserve la commande en ligne puis dirige vers son paiement reprenable', function (): void {
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
        ->assertNotDispatched('open-kkiapay')
        ->assertRedirect();

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
