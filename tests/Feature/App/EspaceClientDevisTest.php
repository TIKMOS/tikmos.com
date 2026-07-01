<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

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
