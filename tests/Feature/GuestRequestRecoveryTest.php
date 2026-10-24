<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\RequestEvent;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\GuestRequestEmailVerification;
use App\Notifications\GuestRequestRecoveryLink;
use App\Notifications\QuoteSent;
use App\Notifications\ServiceRequestStatusChanged;
use App\Notifications\ServiceRequestSubmitted;
use App\Services\GuestRequestRecoveryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Http::preventStrayRequests();
    Notification::fake();
});

function recoveryClient(array $attributes = []): User
{
    $client = User::factory()->create($attributes);
    $client->assignRole(Role::Client->value);

    return $client;
}

function guestRecoveryRequest(User $client): ServiceRequest
{
    return ServiceRequest::factory()->create(['user_id' => null, 'email' => $client->email, 'message' => 'Besoin confidentiel F05']);
}

function guestRecoveryLink(ServiceRequest $request): string
{
    return app(GuestRequestRecoveryService::class)->linkFor($request);
}

it('permet au destinataire vérifié de récupérer une demande invitée sans ouvrir les accès croisés', function (): void {
    $client = User::factory()->create();
    $client->assignRole(Role::Client->value);
    $request = ServiceRequest::factory()->create(['user_id' => null, 'email' => $client->email]);
    $other = User::factory()->create();
    $other->assignRole(Role::Client->value);

    $this->actingAs($client)->get(route('app.requests.show', $request))->assertForbidden();
    $url = (new QuoteSent($request))->toMail($client)->actionUrl;
    $this->actingAs($client)->get($url)->assertOk();
    $this->post($url)->assertRedirect(route('app.requests.show', $request));
    expect($request->fresh()->user_id)->toBe($client->id);
    $this->get(route('app.requests.show', $request))->assertOk();
    $this->actingAs($other)->get(route('app.requests.show', $request))->assertForbidden();
});

it('ne rattache rien sur un GET et ne conserve aucun jeton en clair en base', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $token = basename(parse_url($url, PHP_URL_PATH));
    $entry = DB::table('guest_request_claims')->sole();
    expect($entry->token_hash)->toBe(hash('sha256', $token))->not->toBe($token);
    $this->actingAs($client)->get($url)->assertOk()->assertDontSee($request->message)
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($request->fresh()->user_id)->toBeNull()->and($entry->used_at)->toBeNull();
});

it('refuse une signature absente ou altérée sans exposer la demande', function (string $change): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $url = $change === 'missing' ? strtok($url, '?') : $url.'0';
    $this->actingAs($client)->get($url)->assertForbidden()->assertDontSee($request->message)->assertDontSee($request->reference);
    $this->post($url)->assertForbidden();
    expect($request->fresh()->user_id)->toBeNull();
})->with(['missing', 'tampered']);

it('refuse un lien expiré même si une nouvelle signature est fabriquée autour du même jeton', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $token = basename(parse_url($url, PHP_URL_PATH));
    $this->travel(25)->hours();
    $this->actingAs($client)->get($url)->assertForbidden();
    $this->post($url)->assertForbidden();
    $newSignature = URL::temporarySignedRoute('request-recovery.show', now()->addDay(), ['token' => $token]);
    $this->get($newSignature)->assertForbidden();
    $this->post($newSignature)->assertForbidden();
    expect($request->fresh()->user_id)->toBeNull();
});

it('revérifie l’expiration quand la page a été ouverte avant la limite', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->actingAs($client)->get($url)->assertOk();
    $this->travel(25)->hours();
    $this->post($url)->assertForbidden();
    expect($request->fresh()->user_id)->toBeNull();
});

it('refuse le mauvais compte sans consommer le lien puis accepte le destinataire', function (): void {
    $client = recoveryClient();
    $other = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->actingAs($other)->get($url)->assertSee('Ce compte ne permet pas')->assertDontSee($request->message)->assertDontSee($request->email);
    $this->post($url)->assertForbidden();
    $this->post($url, ['intent' => 'verify'])->assertForbidden();
    Notification::assertNothingSent();
    expect(DB::table('guest_request_claims')->value('used_at'))->toBeNull();
    $this->actingAs($client)->post($url)->assertRedirect(route('app.requests.show', $request));
});

it('exige une adresse vérifiée même avec deux emails identiques', function (): void {
    $client = recoveryClient(['email_verified_at' => null]);
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->actingAs($client)->get($url)->assertSee('Vérifiez l’adresse email');
    $this->post($url)->assertForbidden();
    expect($request->fresh()->user_id)->toBeNull();
    $this->from($url)->post($url, ['intent' => 'verify'])->assertRedirect($url);
    Notification::assertSentTo($client, GuestRequestEmailVerification::class);
    $notification = Notification::sent($client, GuestRequestEmailVerification::class)->first();
    $verification = $notification->toMail($client)->actionUrl;
    $return = $this->get($verification)->assertRedirect()->headers->get('Location');
    expect($client->fresh()->hasVerifiedEmail())->toBeTrue()->and($request->fresh()->user_id)->toBeNull();
    $this->get($return)->assertSee('Rattacher ma demande');
    $this->post($return)->assertRedirect(route('app.requests.show', $request));
});

it('limite les renvois de vérification et ne les envoie jamais à une adresse fournie par le formulaire', function (): void {
    $client = recoveryClient(['email_verified_at' => null]);
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->actingAs($client)->from($url)->post($url, ['intent' => 'verify', 'email' => 'intrus@example.test'])->assertRedirect();
    $this->post($url, ['intent' => 'verify'])->assertRedirect();
    Notification::assertSentToTimes($client, GuestRequestEmailVerification::class, 1);
    expect($client->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuse une vérification par un autre compte ou avec un lien altéré ou expiré', function (string $case): void {
    $client = recoveryClient(['email_verified_at' => null]);
    $other = recoveryClient(['email_verified_at' => null]);
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $token = basename(parse_url($url, PHP_URL_PATH));
    $verification = (new GuestRequestEmailVerification($token))->toMail($client)->actionUrl;
    $this->actingAs($case === 'other' ? $other : $client);
    if ($case === 'tampered') {
        $verification .= '0';
    } elseif ($case === 'expired') {
        $this->travel(61)->minutes();
    } elseif ($case === 'changed-email') {
        $client->update(['email' => 'nouvelle@example.test']);
    }
    $this->get($verification)->assertForbidden()->assertDontSee($request->message);
    expect($client->fresh()->hasVerifiedEmail())->toBeFalse()->and($other->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($request->fresh()->user_id)->toBeNull();
})->with(['other', 'tampered', 'expired', 'changed-email']);

it('conserve le retour au lien après connexion sans rattachement automatique', function (): void {
    $client = recoveryClient(['password' => 'secret123']);
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->get($url)->assertOk()->assertSee('Se connecter')->assertDontSee($request->message);
    Volt::test('pages.auth.login')->set('email', $client->email)->set('password', 'secret123')->call('login')->assertRedirect($url);
    expect($request->fresh()->user_id)->toBeNull();
    $this->post($url)->assertRedirect(route('app.requests.show', $request));
});

it('conserve le retour après inscription et demande ensuite de vérifier la nouvelle adresse', function (): void {
    $request = ServiceRequest::factory()->create(['user_id' => null, 'email' => 'nouveau@example.test']);
    $url = guestRecoveryLink($request);
    $this->get($url)->assertOk();
    Volt::test('pages.auth.register')->set('name', 'Client fictif')->set('email', $request->email)
        ->set('password', 'secret123')->set('password_confirmation', 'secret123')->call('register')->assertRedirect($url);
    $client = User::where('email', $request->email)->sole();
    expect($client->hasVerifiedEmail())->toBeFalse()->and($request->fresh()->user_id)->toBeNull();
    $this->get($url)->assertSee('Vérifiez l’adresse email');
    $this->post($url)->assertForbidden();
});

it('ne transfère jamais une demande déjà attribuée même avec le bon email', function (): void {
    $client = recoveryClient();
    $owner = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $request->update(['user_id' => $owner->id]);
    $this->actingAs($client)->get($url)->assertForbidden();
    $this->post($url)->assertForbidden();
    expect($request->fresh()->user_id)->toBe($owner->id);
});

it('invalide tous les liens de la demande lors du rattachement et conserve les décisions et l’historique', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $quote = Quote::create([
        'service_request_id' => $request->id, 'reference' => 'TKM-Q-F05', 'amount' => 12345, 'currency' => 'XOF',
        'status' => QuoteStatus::Accepted, 'valid_until' => now()->subDay(), 'decided_at' => now()->subDays(2),
    ]);
    $request->update(['status' => ServiceRequestStatus::Accepted]);
    $event = $request->events()->create(['from_status' => null, 'to_status' => $request->status->value, 'note' => 'Historique conservé']);
    $snapshot = $quote->fresh()->getRawOriginal();
    $url = guestRecoveryLink($request);
    $second = guestRecoveryLink($request);
    $this->actingAs($client)->post($url)->assertRedirect();
    $this->post($url)->assertForbidden()->assertSee('déjà été utilisé');
    $this->post($second)->assertForbidden();
    expect($quote->fresh()->getRawOriginal())->toBe($snapshot)
        ->and($request->fresh()->status)->toBe(ServiceRequestStatus::Accepted)
        ->and($event->fresh()->note)->toBe('Historique conservé')
        ->and($request->events()->count())->toBe(2)
        ->and(DB::table('guest_request_claims')->whereNull('used_at')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('annule le rattachement et la consommation si la journalisation échoue', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $token = basename(parse_url($url, PHP_URL_PATH));
    $dispatcher = RequestEvent::getEventDispatcher();
    RequestEvent::setEventDispatcher(clone $dispatcher);
    RequestEvent::creating(fn () => throw new RuntimeException('Journal indisponible'));
    try {
        expect(fn () => app(GuestRequestRecoveryService::class)->claim($token, $client))->toThrow(RuntimeException::class);
    } finally {
        RequestEvent::setEventDispatcher($dispatcher);
    }
    expect($request->fresh()->user_id)->toBeNull()->and(DB::table('guest_request_claims')->value('used_at'))->toBeNull();
});

it('revérifie le compte et la demande dans le service même après ouverture de la page', function (string $change): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $url = guestRecoveryLink($request);
    $this->actingAs($client)->get($url)->assertOk();
    match ($change) {
        'recipient' => $request->update(['email' => 'autre@example.test']),
        'unverified' => $client->forceFill(['email_verified_at' => null])->save(),
        'suspended' => $client->update(['status' => 'suspended']),
        'deleted' => $request->delete(),
    };
    $token = basename(parse_url($url, PHP_URL_PATH));
    expect(fn () => app(GuestRequestRecoveryService::class)->claim($token, $client))->toThrow(ValidationException::class);
    expect(DB::table('guest_request_claims')->value('used_at'))->toBeNull();
})->with(['recipient', 'unverified', 'suspended', 'deleted']);

it('permet le renouvellement des anciennes demandes sans compte avec une réponse non énumérable', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $old = guestRecoveryLink($request);
    $this->travel(25)->hours();
    $response = $this->from(route('request-recovery.renew'))->post(route('request-recovery.renew.send'), [
        'reference' => $request->reference, 'email' => $client->email,
    ])->assertRedirect();
    $message = session('status');
    Notification::assertSentOnDemand(GuestRequestRecoveryLink::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $client->email);
    $notification = Notification::sent(new AnonymousNotifiable, GuestRequestRecoveryLink::class)->first();
    $new = $notification->toMail($client)->actionUrl;
    $this->actingAs($client)->get($old)->assertForbidden();
    $this->post($new)->assertRedirect(route('app.requests.show', $request));
    $this->post(route('request-recovery.renew.send'), ['reference' => 'INCONNUE', 'email' => $client->email])->assertRedirect();
    expect(session('status'))->toBe($message);
});

it('ne renvoie rien pour une adresse différente une demande liée ou sans email', function (string $case): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    if ($case === 'owned') {
        $request->update(['user_id' => $client->id]);
    } elseif ($case === 'no-email') {
        $request->update(['email' => null]);
    }
    $this->from(route('request-recovery.renew'))->post(route('request-recovery.renew.send'), [
        'reference' => $request->reference, 'email' => $case === 'wrong-email' ? 'intrus@example.test' : $client->email,
    ])->assertRedirect()->assertSessionHas('status');
    Notification::assertNothingSent();
    expect(DB::table('guest_request_claims')->count())->toBe(0);
})->with(['owned', 'no-email', 'wrong-email']);

it('limite les renouvellements sans invalider les liens encore valides', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    $old = guestRecoveryLink($request);
    for ($i = 0; $i < 2; $i++) {
        $this->from(route('request-recovery.renew'))->post(route('request-recovery.renew.send'), [
            'reference' => $request->reference, 'email' => $client->email,
        ])->assertRedirect();
    }
    Notification::assertSentOnDemandTimes(GuestRequestRecoveryLink::class, 1);
    $this->actingAs($client)->post($old)->assertRedirect();
});

it('adapte les trois notifications client et conserve les liens directs pour les demandes déjà liées', function (): void {
    $client = recoveryClient();
    $request = guestRecoveryRequest($client);
    foreach ([QuoteSent::class, ServiceRequestSubmitted::class, ServiceRequestStatusChanged::class] as $class) {
        $url = (new $class($request))->toMail($client)->actionUrl;
        expect($url)->toContain('/retrouver-demande/')->and(URL::hasValidSignature(Request::create($url)))->toBeTrue();
    }
    $request->update(['user_id' => $client->id]);
    expect((new QuoteSent($request))->toMail($client)->actionUrl)->toBe(route('app.requests.show', $request));
});

it('couvre le dépôt public puis l’envoi du devis et la récupération du destinataire', function (): void {
    $client = recoveryClient();
    $service = Service::factory()->create();
    Volt::test('pages.public.services-show', ['service' => $service])->set('name', 'Client recette')
        ->set('email', $client->email)->set('message', 'Besoin confidentiel à récupérer')->call('submit')->assertHasNoErrors();
    $request = ServiceRequest::where('email', $client->email)->sole();
    expect($request->user_id)->toBeNull();
    $staff = User::factory()->create();
    $staff->assignRole(Role::Commercial->value);
    Volt::actingAs($staff)->test('pages.admin.service-request-show', ['request' => $request])
        ->set('amount', 10000)->set('validUntil', now()->addDay()->toDateString())->call('saveQuote')->assertHasNoErrors();
    Notification::assertSentOnDemand(QuoteSent::class);
    $mail = Notification::sent(new AnonymousNotifiable, QuoteSent::class)->first()->toMail($client);
    $this->actingAs($client)->get(route('app.requests.show', $request))->assertForbidden();
    $this->post($mail->actionUrl)->assertRedirect(route('app.requests.show', $request));
    $this->get(route('app.requests.show', $request))->assertOk()->assertSee($request->message);
    expect($request->fresh()->quote->amount)->toBe(10000)->and($request->fresh()->quote->status)->toBe(QuoteStatus::Sent);
});
