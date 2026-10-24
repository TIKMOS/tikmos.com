<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\RequestEvent;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\QuoteService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Http::preventStrayRequests();
    Notification::fake();
});

function devisClient(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

function quotedRequest(User $user): ServiceRequest
{
    $request = ServiceRequest::factory()->status(ServiceRequestStatus::Quoted)->create(['user_id' => $user->id]);
    Quote::create([
        'service_request_id' => $request->id, 'reference' => 'TKM-Q-TEST01',
        'amount' => 500000, 'currency' => 'XOF', 'status' => QuoteStatus::Sent, 'sent_at' => now(),
    ]);

    return $request->fresh();
}

it('affiche le tableau de bord client Tikmos', function (): void {
    $this->actingAs(devisClient())->get('/app')
        ->assertOk()
        ->assertSee('Espace client')
        ->assertSee('Demander un devis');
});

it('liste les demandes du client connecté uniquement', function (): void {
    $client = devisClient();
    $mine = ServiceRequest::factory()->create(['user_id' => $client->id, 'name' => 'Ma demande à moi']);
    $other = ServiceRequest::factory()->create(['user_id' => devisClient()->id, 'name' => 'Demande autrui']);

    Volt::actingAs($client)->test('pages.app.requests-index')
        ->assertSee($mine->reference)
        ->assertDontSee($other->reference);
});

it('interdit l’accès à la demande d’un autre client (403)', function (): void {
    $owner = devisClient();
    $intruder = devisClient();
    $request = ServiceRequest::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)->get(route('app.requests.show', $request->reference))->assertForbidden();
    $this->actingAs($owner)->get(route('app.requests.show', $request->reference))->assertOk();
});

it('permet au client d’accepter son devis', function (): void {
    $client = devisClient();
    $request = quotedRequest($client);

    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('acceptQuote');

    $request->refresh();
    expect($request->status)->toBe(ServiceRequestStatus::Accepted)
        ->and($request->quote->status)->toBe(QuoteStatus::Accepted)
        ->and($request->events()->count())->toBeGreaterThanOrEqual(1);
});

it('permet au client de refuser son devis', function (): void {
    $client = devisClient();
    $request = quotedRequest($client);

    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('refuseQuote');

    $request->refresh();
    expect($request->status)->toBe(ServiceRequestStatus::Refused)
        ->and($request->quote->status)->toBe(QuoteStatus::Refused);
});

it('refuse un devis expiré même par appel Livewire direct', function (): void {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['valid_until' => '2026-10-23']);
    $beforeEvents = $request->events()->count();

    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('acceptQuote');

    $request->refresh();
    expect($request->quote->status)->toBe(QuoteStatus::Sent)
        ->and($request->quote->decided_at)->toBeNull()
        ->and($request->quote->amount)->toBe(500000)
        ->and($request->status)->toBe(ServiceRequestStatus::Quoted)
        ->and($request->events()->count())->toBe($beforeEvents);
    $component->assertHasErrors('quote')->assertSee('Expiré')->assertDontSee('Accepter le devis');
    Notification::assertNothingSent();
});

it('respecte la fin du jour de validité dans le fuseau configuré', function (string $timezone, string $instant, bool $accepted): void {
    config(['app.timezone' => $timezone]);
    $this->travelTo(Carbon::parse($instant, 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['valid_until' => '2026-10-24']);

    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('acceptQuote');

    $request->refresh();
    expect($request->quote->status)->toBe($accepted ? QuoteStatus::Accepted : QuoteStatus::Sent)
        ->and($request->status)->toBe($accepted ? ServiceRequestStatus::Accepted : ServiceRequestStatus::Quoted)
        ->and($request->events()->count())->toBe($accepted ? 1 : 0);
    if ($accepted) {
        $component->assertHasNoErrors();
        expect($request->quote->decided_at)->not->toBeNull();
    } else {
        $component->assertHasErrors('quote');
        expect($request->quote->decided_at)->toBeNull();
    }
})->with([
    ['UTC', '2026-10-24 00:00:00', true],
    ['UTC', '2026-10-24 23:59:59.999999', true],
    ['UTC', '2026-10-25 00:00:00', false],
    ['Africa/Lagos', '2026-10-24 22:59:59.999999', true],
    ['Africa/Lagos', '2026-10-24 23:00:00', false],
    ['America/New_York', '2026-10-25 03:59:59.999999', true],
    ['America/New_York', '2026-10-25 04:00:00', false],
]);

it('revérifie la validité si la page est restée ouverte pendant le passage à minuit', function (): void {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-24 23:59:59', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['valid_until' => '2026-10-24']);
    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->assertSee('Accepter le devis');

    $this->travelTo(Carbon::parse('2026-10-25 00:00:00', 'UTC'));
    $component->call('acceptQuote')->assertHasErrors('quote')
        ->assertSee('Expiré')->assertSee('Demander le renouvellement')->assertDontSee('Accepter le devis');
    expect($request->quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($request->events()->count())->toBe(0);
});

it('relit une date modifiée en base après ouverture de la page', function (): void {
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['valid_until' => '2026-10-25']);
    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request]);
    $request->quote->update(['valid_until' => '2026-10-23']);

    $component->call('acceptQuote')->assertHasErrors('quote')->assertSee('Expiré');
    expect($request->quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($request->events()->count())->toBe(0);
});

it('préserve un devis sans date de validité', function (): void {
    $this->travelTo(Carbon::parse('2035-01-01 00:00:00', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);

    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->assertDontSee('Ce devis a expiré')->call('acceptQuote')->assertHasNoErrors();
    expect($request->quote->fresh()->status)->toBe(QuoteStatus::Accepted);
});

it('affiche le renouvellement sans réécrire le devis historique expiré', function (): void {
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['valid_until' => '2026-10-23']);
    $before = $request->quote->getAttributes();

    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->assertSee('Expiré')->assertSee($request->quote->reference)
        ->assertSee('Demander le renouvellement')->assertSee(route('contact'))
        ->assertDontSee('Accepter le devis');

    expect($request->quote->fresh()->getAttributes())->toBe($before)
        ->and($request->events()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('conserve les décisions déjà enregistrées malgré une date passée et les appels répétés', function (QuoteStatus $status, ServiceRequestStatus $requestStatus): void {
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $client = devisClient();
    $request = quotedRequest($client);
    $request->quote->update(['status' => $status, 'valid_until' => '2026-10-23', 'decided_at' => now()->subDays(2)]);
    $request->transitionTo($requestStatus, $client, 'Décision historique.');
    $before = $request->quote->fresh()->getAttributes();
    $events = $request->events()->count();

    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->assertSee($status->label())->assertDontSee('Ce devis a expiré')
        ->call('acceptQuote')->assertStatus(422);
    Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('refuseQuote')->assertStatus(422);

    expect($request->quote->fresh()->getAttributes())->toBe($before)
        ->and($request->fresh()->status)->toBe($requestStatus)
        ->and($request->events()->count())->toBe($events);
})->with([
    [QuoteStatus::Accepted, ServiceRequestStatus::Accepted],
    [QuoteStatus::Refused, ServiceRequestStatus::Refused],
]);

it('ne crée pas deux décisions ni deux événements en rejouant une acceptation', function (): void {
    $client = devisClient();
    $request = quotedRequest($client);
    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request])
        ->call('acceptQuote')->assertHasNoErrors();
    $decidedAt = $request->quote->fresh()->decided_at->toDateTimeString();

    $component->call('acceptQuote')->assertStatus(422);
    expect($request->events()->count())->toBe(1)
        ->and($request->quote->fresh()->decided_at->toDateTimeString())->toBe($decidedAt);
});

it('revérifie le propriétaire de la demande au moment de la décision', function (): void {
    $client = devisClient();
    $request = quotedRequest($client);
    $component = Volt::actingAs($client)->test('pages.app.requests-show', ['request' => $request]);
    $request->update(['user_id' => devisClient()->id]);

    $component->call('acceptQuote')->assertForbidden();
    expect($request->quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($request->events()->count())->toBe(0);
});

it('annule la décision si l’écriture de son historique échoue', function (): void {
    $client = devisClient();
    $request = quotedRequest($client);
    Event::listen('eloquent.creating: '.RequestEvent::class, function (): void {
        throw new RuntimeException('Échec historique simulé');
    });

    expect(fn () => app(QuoteService::class)->decide($request, $client, QuoteStatus::Accepted))
        ->toThrow(RuntimeException::class, 'Échec historique simulé');
    expect($request->quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($request->quote->fresh()->decided_at)->toBeNull()
        ->and($request->fresh()->status)->toBe(ServiceRequestStatus::Quoted)
        ->and($request->events()->count())->toBe(0);
});
