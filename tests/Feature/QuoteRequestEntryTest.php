<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\Role;
use App\Models\Lead;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\GuestRequestRecoveryService;
use App\Services\PublicFormSubmissionService;
use App\Settings\GeneralSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
    $this->seed(RolePermissionSeeder::class);
    $settings = app(GeneralSettings::class);
    $settings->emails = ['contact' => 'equipe@example.test'];
    $settings->save();
});

function quoteEntryData(): array
{
    return ['name' => 'Client fictif', 'email' => 'client@example.test', 'phone' => '',
        'company' => 'Structure fictive', 'message' => 'Besoin fictif à étudier', 'budget_hint' => 'À préciser'];
}

it('dirige tous les boutons publics de devis vers le parcours suivi', function (string $path): void {
    $html = $this->get($path)->assertOk()->getContent();
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $urls = [];
    foreach ($document->getElementsByTagName('a') as $anchor) {
        if (trim(preg_replace('/\s+/u', ' ', $anchor->textContent)) === 'Demander un devis') {
            $urls[] = $anchor->getAttribute('href');
        }
    }
    // Chaque page contient au minimum le bouton principal du layout.
    expect($urls)->not->toBeEmpty();
    foreach ($urls as $url) {
        expect($url)->toBe(route('quote-request'));
    }
})->with(['/', '/a-propos', '/temoignages', '/services', '/realisations']);

it('cree seulement une demande suivie avec service selectionne ou besoin sur mesure', function (bool $selected): void {
    $service = Service::factory()->create();
    $page = Volt::test('pages.public.services-show');
    foreach (quoteEntryData() as $key => $value) {
        $page->set($key, $value);
    }
    $page->set('serviceId', $selected ? (string) $service->id : '')->call('submit')->assertHasNoErrors();
    $request = ServiceRequest::sole();
    expect($request->service_id)->toBe($selected ? $service->id : null)
        ->and($request->user_id)->toBeNull()->and($request->events()->count())->toBe(1)
        ->and(Lead::count())->toBe(0);
    $page->assertSee($request->reference)->assertSee('Recevoir un nouveau lien par email');
    Notification::assertCount(2);
})->with([true, false]);

it('offre le suivi au compte connecte et expose la demande au pipeline autorise', function (): void {
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);
    $page = Volt::actingAs($client)->test('pages.public.services-show')->set('message', 'Projet fictif')->call('submit')->assertHasNoErrors();
    $request = ServiceRequest::sole();
    $page->assertSee('Suivre ma demande')->assertSee(route('app.requests.show', $request), false);
    expect($request->user_id)->toBe($client->id);
    $this->get(route('app.requests.show', $request))->assertOk()->assertSee('Projet fictif');
    $other = User::factory()->create();
    $other->assignRole(Role::Client->value);
    $this->actingAs($other)->get(route('app.requests.show', $request))->assertForbidden();
    $staff = User::factory()->create();
    $staff->assignRole(Role::Commercial->value);
    Volt::actingAs($staff)->test('pages.admin.service-requests')->assertSee($request->reference);
});

it('ne rattache pas un invite par simple egalite de son email', function (): void {
    $client = User::factory()->unverified()->create(['email' => 'client@example.test']);
    $client->assignRole(Role::Client->value);
    app(PublicFormSubmissionService::class)->quote((string) Str::uuid(), quoteEntryData(), null);
    $request = ServiceRequest::sole();
    expect($request->user_id)->toBeNull();
    $this->actingAs($client)->get(route('app.requests.show', $request))->assertForbidden();
    $url = app(GuestRequestRecoveryService::class)->linkFor($request);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    expect($query)->toHaveKeys(['expires', 'signature']);
    $token = basename((string) parse_url($url, PHP_URL_PATH));
    expect(app(GuestRequestRecoveryService::class)->state($token, $client))->toBe('unverified');
    $client->markEmailAsVerified();
    app(GuestRequestRecoveryService::class)->claim($token, $client);
    expect($request->fresh()->user_id)->toBe($client->id);
});

it('conserve le contact general dans le seul pipeline Lead', function (): void {
    Volt::test('pages.public.contact')->assertSet('subject', 'Question générale')
        ->set('name', 'Client fictif')->set('email', 'contact@example.test')->set('message', 'Question fictive')
        ->call('submit')->assertHasNoErrors()->assertSet('sent', true);
    expect(Lead::sole()->message)->toBe('[Question générale] Question fictive')
        ->and(ServiceRequest::count())->toBe(0);
    Notification::assertNothingSent();
});

it('rejoue un envoi sans dupliquer le resultat ni les notifications ni consommer le quota', function (bool $quote): void {
    $service = app(PublicFormSubmissionService::class);
    $key = (string) Str::uuid();
    $data = quoteEntryData() + ['subject' => 'Question générale'];
    $first = $quote ? $service->quote($key, $data, null) : $service->contact($key, $data);
    $before = $first->fresh()->getRawOriginal();
    for ($i = 0; $i < 4; $i++) {
        $data['message'] = 'Texte altéré';
        $again = $quote ? $service->quote($key, $data, null) : $service->contact($key, $data);
        expect($again->id)->toBe($first->id)->and($again->getRawOriginal())->toBe($before);
    }
    expect(DB::table('public_form_submissions')->count())->toBe(1)
        ->and($quote ? ServiceRequest::count() : Lead::count())->toBe(1);
    Notification::assertCount($quote ? 2 : 0);
    $service->contact((string) Str::uuid(), $data);
    $service->contact((string) Str::uuid(), $data);
    expect(fn () => $service->contact((string) Str::uuid(), $data))->toThrow(ValidationException::class);
    expect(DB::table('public_form_submissions')->count())->toBe(3);
})->with([true, false]);

it('refuse le rejeu depuis un autre acteur sans divulguer la reference', function (): void {
    $service = app(PublicFormSubmissionService::class);
    $key = (string) Str::uuid();
    $service->quote($key, quoteEntryData(), null);
    session()->setId(Str::random(40));
    try {
        $service->quote($key, quoteEntryData(), null);
        $this->fail('Un autre acteur ne doit pas récupérer le reçu.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
    expect(ServiceRequest::count())->toBe(1);
    Notification::assertCount(2);
});

it('refuse un service non public ou retire avant envoi et conserve le besoin', function (string $selection): void {
    $draft = Service::factory()->draft()->create();
    $page = Volt::test('pages.public.services-show')->set('name', 'Fictif')->set('email', 'client@example.test')
        ->set('message', 'Texte conservé')->set('serviceId', $selection === 'draft' ? (string) $draft->id : '999999');
    $page->call('submit')->assertHasErrors('serviceId')->assertSet('message', 'Texte conservé');
    expect(ServiceRequest::count())->toBe(0)->and(DB::table('public_form_submissions')->count())->toBe(0);
    Notification::assertNothingSent();
})->with(['draft', 'missing']);

it('reverifie la publication sur la fiche service ouverte', function (): void {
    $service = Service::factory()->create();
    $page = Volt::test('pages.public.services-show', ['service' => $service])->set('name', 'Fictif')
        ->set('email', 'client@example.test')->set('message', 'Texte conservé');
    $service->update(['status' => Publication::Draft]);
    $page->call('submit')->assertNotFound();
    expect(ServiceRequest::count())->toBe(0)->and(DB::table('public_form_submissions')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('protege aussi la nouvelle entree contre les appels directs au dela du quota', function (): void {
    $page = Volt::test('pages.public.services-show');
    for ($i = 0; $i < 3; $i++) {
        $page->set('name', 'Fictif')->set('email', 'client@example.test')->set('message', 'Besoin fictif')->call('submit')->assertHasNoErrors();
    }
    $page->set('message', 'Texte refusé conservé')->call('submit')->assertHasErrors('submission')->assertSet('message', 'Texte refusé conservé');
    expect(ServiceRequest::count())->toBe(3)->and(DB::table('public_form_submissions')->count())->toBe(3);
    Notification::assertCount(6);
});

it('preserve le honeypot et les validations sur la nouvelle entree', function (): void {
    $page = Volt::test('pages.public.services-show')->set('name', 'Fictif')->set('email', 'invalide')->call('submit')->assertHasErrors(['email', 'message']);
    $page->set('email', 'client@example.test')->set('message', 'Texte fictif')->set('website', 'rempli')->call('submit')->assertHasNoErrors();
    expect(ServiceRequest::count())->toBe(0)->and(DB::table('public_form_submissions')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('ajoute les recus sur une installation existante sans reecrire les donnees', function (): void {
    $lead = Lead::create(['name' => 'Historique fictif', 'message' => 'Message conservé', 'source' => 'site']);
    $request = ServiceRequest::factory()->create();
    $before = [$lead->fresh()->getRawOriginal(), $request->fresh()->getRawOriginal()];
    $migration = require database_path('migrations/2026_10_27_020000_create_public_form_submissions_table.php');
    $migration->down();
    $migration->up();
    expect([$lead->fresh()->getRawOriginal(), $request->fresh()->getRawOriginal()])->toBe($before)
        ->and(DB::table('public_form_submissions')->count())->toBe(0);
});

it('annule la demande et le recu si son historique ne peut pas etre cree', function (): void {
    DB::unprepared("CREATE TRIGGER f12_event_failure BEFORE INSERT ON request_events BEGIN SELECT RAISE(ABORT, 'fixture event failure'); END;");
    try {
        expect(fn () => app(PublicFormSubmissionService::class)->quote((string) Str::uuid(), quoteEntryData(), null))
            ->toThrow(QueryException::class);
        expect(ServiceRequest::count())->toBe(0)->and(DB::table('public_form_submissions')->count())->toBe(0);
        Notification::assertNothingSent();
    } finally {
        DB::unprepared('DROP TRIGGER f12_event_failure');
    }
});

it('refuse la modification du jeton denvoi par un appel Livewire', function (): void {
    expect(fn () => Volt::test('pages.public.services-show')->set('submissionKey', (string) Str::uuid()))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(ServiceRequest::count())->toBe(0);
});

it('F12 ouvre une demande suivie depuis les boutons de devis publics', function (): void {
    $this->get('/')->assertOk()->assertSee('href="'.url('/demander-un-devis').'"', false);
});

it('F12 propose une entree generale distincte du contact', function (): void {
    $this->get('/demander-un-devis')->assertOk()->assertSee('Demander un devis');
});

it('F12 refuse une soumission depuis une identite fermee deja chargee', function (): void {
    $user = User::factory()->create();
    $page = Volt::actingAs($user)->test('pages.public.services-show', ['service' => Service::factory()->create()]);
    $user->delete();
    $page->set('message', 'Projet fictif')->call('submit')->assertForbidden();
    expect(ServiceRequest::count())->toBe(0)->and(Lead::count())->toBe(0);
    Notification::assertNothingSent();
});
