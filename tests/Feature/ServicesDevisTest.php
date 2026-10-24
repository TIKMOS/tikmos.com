<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceCategory;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\QuoteSent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Http::preventStrayRequests();
    Notification::fake();
});

function svcStaff(string $role): User
{
    $u = User::factory()->create();
    $u->assignRole($role);

    return $u;
}

// ---------- Vitrine ----------

it('affiche le catalogue de services publiés', function (): void {
    $this->get('/services')
        ->assertOk()
        ->assertSee('Mes services')
        ->assertSee('Formation Data Science et IA', false);
});

it('filtre le catalogue par catégorie', function (): void {
    Service::factory()->category(ServiceCategory::Orientation)->create(['title' => 'Coaching parcours pro', 'status' => Publication::Published]);
    Service::factory()->category(ServiceCategory::Installation)->create(['title' => 'Câblage réseau bureau', 'status' => Publication::Published]);

    Volt::test('pages.public.services-index')
        ->set('category', ServiceCategory::Orientation->value)
        ->assertSee('Coaching parcours pro')
        ->assertDontSee('Câblage réseau bureau');
});

it('affiche une fiche service publiée et 404 un brouillon', function (): void {
    $published = Service::factory()->create(['title' => 'Audit IA', 'status' => Publication::Published]);
    $draft = Service::factory()->draft()->create(['title' => 'Service caché']);

    $this->get(route('services.show', $published->slug))->assertOk()->assertSee('Audit IA');
    $this->get(route('services.show', $draft->slug))->assertNotFound();
});

// ---------- Demande de devis ----------

it('crée une demande de devis depuis la fiche (invité) avec référence et événement', function (): void {
    $service = Service::factory()->create();

    Volt::test('pages.public.services-show', ['service' => $service])
        ->set('name', 'Awa Diallo')
        ->set('email', 'awa@example.com')
        ->set('message', 'Je voudrais une formation pour mon équipe.')
        ->call('submit')
        ->assertHasNoErrors();

    $request = ServiceRequest::firstWhere('name', 'Awa Diallo');
    expect($request)->not->toBeNull()
        ->and($request->reference)->toStartWith('TKM-DEV-')
        ->and($request->status)->toBe(ServiceRequestStatus::Sent)
        ->and($request->service_id)->toBe($service->id)
        ->and($request->events()->count())->toBe(1);
});

it('rattache la demande au client connecté', function (): void {
    $service = Service::factory()->create();
    $client = svcStaff(Role::Client->value);

    Volt::actingAs($client)->test('pages.public.services-show', ['service' => $service])
        ->set('message', 'Bonjour, un devis svp.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(ServiceRequest::latest()->first()->user_id)->toBe($client->id);
});

it('exige un message et au moins un contact', function (): void {
    $service = Service::factory()->create();

    Volt::test('pages.public.services-show', ['service' => $service])
        ->set('name', 'Test')
        ->set('message', '')
        ->call('submit')
        ->assertHasErrors('message');

    Volt::test('pages.public.services-show', ['service' => $service])
        ->set('name', 'Test')
        ->set('message', 'Un besoin')
        ->set('email', '')
        ->set('phone', '')
        ->call('submit')
        ->assertHasErrors('phone');
});

it('bloque les bots via le honeypot', function (): void {
    $service = Service::factory()->create();

    Volt::test('pages.public.services-show', ['service' => $service])
        ->set('name', 'Bot')
        ->set('email', 'bot@spam.com')
        ->set('message', 'spam')
        ->set('website', 'http://spam.com')
        ->call('submit');

    expect(ServiceRequest::count())->toBe(0);
});

// ---------- Admin : services ----------

it('autorise un éditeur à créer un service et refuse le support', function (): void {
    Volt::actingAs(svcStaff(Role::Editor->value))->test('pages.admin.services')
        ->call('create')
        ->set('title', 'Nouveau service IA')
        ->set('category', ServiceCategory::Conception->value)
        ->set('status', Publication::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(Service::firstWhere('title', 'Nouveau service IA'))->not->toBeNull();

    // Le support n'a pas services.manage → route interdite.
    $this->actingAs(svcStaff(Role::Support->value))->get('/admin/services')->assertForbidden();
});

// ---------- Admin : pipeline & devis ----------

it('change le statut et journalise l’événement (commercial)', function (): void {
    $request = ServiceRequest::factory()->create();

    Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->call('transition', ServiceRequestStatus::Studying->value)
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(ServiceRequestStatus::Studying)
        ->and($request->events()->count())->toBe(1);
});

it('crée un devis, envoie, et passe la demande en « Devis envoyé »', function (): void {
    $request = ServiceRequest::factory()->create();

    Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 750000)
        ->set('currency', 'XOF')
        ->call('saveQuote')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->quote)->not->toBeNull()
        ->and($request->quote->amount)->toBe(750000)
        ->and($request->status)->toBe(ServiceRequestStatus::Quoted);
});

it('interdit l’envoi de devis sans permission quotes.send', function (): void {
    $request = ServiceRequest::factory()->create();

    Volt::actingAs(svcStaff(Role::Editor->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 1000)
        ->call('saveQuote')
        ->assertForbidden();
});

it('refuse une date passée ou mal formée lors d’un nouvel envoi sans écritures ni notification', function (string $date): void {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $request = ServiceRequest::factory()->create();
    $before = $request->fresh()->getAttributes();

    Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 750000)->set('validUntil', $date)->call('saveQuote')
        ->assertHasErrors('validUntil')->assertNotDispatched('tk-toast');

    expect(Quote::count())->toBe(0)
        ->and($request->fresh()->getAttributes())->toBe($before)
        ->and($request->events()->count())->toBe(0);
    Notification::assertNothingSent();
})->with(['2026-10-23', '2026-02-30', 'pas-une-date', '2026-10-24T23:59:59Z']);

it('autorise une date du jour, future ou aucune échéance pour un nouvel envoi', function (string $date): void {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-24 23:59:59', 'UTC'));
    $client = svcStaff(Role::Client->value);
    $request = ServiceRequest::factory()->create(['user_id' => $client->id]);

    Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 750000)->set('validUntil', $date)->call('saveQuote')->assertHasNoErrors();

    $request->refresh();
    expect($request->quote->valid_until?->format('Y-m-d'))->toBe($date ?: null)
        ->and($request->quote->status)->toBe(QuoteStatus::Sent)
        ->and($request->status)->toBe(ServiceRequestStatus::Quoted)
        ->and($request->events()->count())->toBe(1);
    Notification::assertSentToTimes($client, QuoteSent::class, 1);
})->with(['2026-10-24', '2026-10-25', '']);

it('revérifie la date d’envoi si le formulaire est resté ouvert après minuit', function (): void {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-24 23:59:59', 'UTC'));
    $request = ServiceRequest::factory()->create();
    $component = Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 10000)->set('validUntil', '2026-10-24');
    $this->travelTo(Carbon::parse('2026-10-25 00:00:00', 'UTC'));

    $component->call('saveQuote')->assertHasErrors('validUntil');
    expect(Quote::count())->toBe(0)->and($request->events()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('utilise la date du fuseau applicatif lors de l’envoi', function (): void {
    config(['app.timezone' => 'Africa/Lagos']);
    $this->travelTo(Carbon::parse('2026-10-24 23:30:00', 'UTC'));
    $request = ServiceRequest::factory()->create();
    $component = Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 10000)->set('validUntil', '2026-10-24');

    $component->call('saveQuote')->assertHasErrors('validUntil');
    expect(Quote::count())->toBe(0);
    $component->set('validUntil', '2026-10-25')->call('saveQuote')->assertHasNoErrors();
    expect($request->fresh()->quote->valid_until->format('Y-m-d'))->toBe('2026-10-25');
});

it('conserve un devis historique expiré lors d’un renvoi invalide puis permet son renouvellement explicite', function (): void {
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $request = ServiceRequest::factory()->status(ServiceRequestStatus::Quoted)->create();
    $quote = Quote::create([
        'service_request_id' => $request->id, 'reference' => 'TKM-Q-HISTORIQUE',
        'amount' => 500000, 'currency' => 'XOF', 'status' => QuoteStatus::Sent,
        'valid_until' => '2026-10-23', 'sent_at' => now()->subDays(3),
    ]);
    $before = $quote->fresh()->getAttributes();
    $component = Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request])
        ->assertSee('Expiré');
    expect($quote->fresh()->getAttributes())->toBe($before);

    $component->set('amount', 600000)->call('saveQuote')->assertHasErrors('validUntil');
    expect($quote->fresh()->getAttributes())->toBe($before)->and($request->events()->count())->toBe(0);
    Notification::assertNothingSent();

    $component->set('validUntil', '2026-10-25')->call('saveQuote')->assertHasNoErrors();
    expect(Quote::count())->toBe(1)
        ->and($quote->fresh()->reference)->toBe('TKM-Q-HISTORIQUE')
        ->and($quote->fresh()->amount)->toBe(600000)
        ->and($quote->fresh()->valid_until->format('Y-m-d'))->toBe('2026-10-25')
        ->and($quote->fresh()->decided_at)->toBeNull();
});

it('interdit le renvoi d’un devis déjà décidé même depuis un ancien formulaire ouvert', function (QuoteStatus $status, ServiceRequestStatus $requestStatus): void {
    $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
    $request = ServiceRequest::factory()->status(ServiceRequestStatus::Quoted)->create();
    $quote = Quote::create([
        'service_request_id' => $request->id, 'reference' => 'TKM-Q-DECIDE',
        'amount' => 500000, 'currency' => 'XOF', 'status' => QuoteStatus::Sent,
        'valid_until' => '2026-10-25', 'sent_at' => now(),
    ]);
    $component = Volt::actingAs(svcStaff(Role::Commercial->value))->test('pages.admin.service-request-show', ['request' => $request]);
    $quote->update(['status' => $status, 'decided_at' => now()]);
    $request->transitionTo($requestStatus, null, 'Décision historique.');
    $before = $quote->fresh()->getAttributes();
    $events = $request->events()->count();

    $component->set('amount', 900000)->call('saveQuote')->assertHasErrors('quote')
        ->assertSee('Son montant et sa décision sont conservés.');
    expect($quote->fresh()->getAttributes())->toBe($before)
        ->and($request->fresh()->status)->toBe($requestStatus)
        ->and($request->events()->count())->toBe($events);
    Notification::assertNothingSent();
})->with([
    [QuoteStatus::Accepted, ServiceRequestStatus::Accepted],
    [QuoteStatus::Refused, ServiceRequestStatus::Refused],
]);
