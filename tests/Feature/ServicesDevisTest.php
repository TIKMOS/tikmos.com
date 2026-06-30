<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\Role;
use App\Enums\ServiceCategory;
use App\Enums\ServiceRequestStatus;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

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
