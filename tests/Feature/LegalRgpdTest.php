<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\LegalDocument;
use App\Models\Order;
use App\Models\Quote;
use App\Models\RequestEvent;
use App\Models\ServiceRequest;
use App\Models\TrustedDevice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
    Http::preventStrayRequests();
});

it('F11 exige une confirmation serveur avant la fermeture directe', function (): void {
    $client = rgpdClient();
    Volt::actingAs($client)->test('pages.app.compte')->call('deleteAccount')
        ->assertHasErrors(['closurePassword', 'closureConfirmed']);

    expect(User::find($client->id))->not->toBeNull();
    $this->assertAuthenticatedAs($client);
});

it('refuse les actions directes depuis une instance authentifiee devenue obsolete', function (string $action): void {
    $client = rgpdClient();
    $page = Volt::actingAs($client)->test('pages.app.compte');
    $client->delete();

    $page->call($action, ...($action === 'revokeDevice' ? [1] : []))->assertForbidden();
})->with(['save', 'revokeDevice', 'deleteAccount', 'exportData']);

function rgpdClient(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('affiche les pages légales Tikmos (sans mention Sezy)', function (): void {
    $this->get('/cgu')->assertOk()->assertSee("Conditions générales d'utilisation")->assertDontSee('SEZY', false);
    $this->get('/confidentialite')->assertOk()->assertSee('Confidentialité')->assertDontSee('SEZY', false);
});

it('expose les CGV de la boutique (paiement à la livraison)', function (): void {
    $this->get('/cgv')
        ->assertOk()
        ->assertSee('Conditions générales de vente')
        ->assertSee('paiement s\'effectue à la livraison', false);
});

it('permet au client d’exporter ses données (RGPD)', function (): void {
    Volt::actingAs(rgpdClient())->test('pages.app.compte')
        ->call('exportData')
        ->assertFileDownloaded('mes-donnees-tikmos.json');
});

it('ferme lacces du client sans effacer physiquement son profil', function (): void {
    $client = rgpdClient();
    $password = $client->password;
    $request = ServiceRequest::factory()->create(['user_id' => $client->id]);
    $quote = Quote::create(['service_request_id' => $request->id, 'reference' => 'F11-DEVIS', 'amount' => 10000,
        'currency' => 'XOF', 'status' => QuoteStatus::Accepted, 'decided_at' => now()]);
    $event = RequestEvent::create(['service_request_id' => $request->id, 'user_id' => $client->id,
        'to_status' => ServiceRequestStatus::Accepted, 'note' => 'Historique fictif']);
    $order = Order::create(['number' => 'F11-COMMANDE', 'user_id' => $client->id, 'subtotal' => 10000, 'total' => 10000,
        'contact_name' => 'Client fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Adresse fictive']);
    $item = $order->items()->create(['name' => 'Article fictif', 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000]);
    $payment = $order->payments()->create(['provider' => 'cod', 'reference' => 'F11-PAIEMENT', 'amount' => 10000, 'state' => 'pending']);
    $paymentEvent = $payment->recordEvent('initialized');
    $device = TrustedDevice::create(['user_id' => $client->id, 'token' => hash('sha256', Str::random(48)), 'expires_at' => now()->addDay()]);
    $models = [$request, $quote, $event, $order, $item, $payment, $paymentEvent, $device];
    $snapshots = array_map(fn ($model) => $model->fresh()->getRawOriginal(), $models);

    Volt::actingAs($client)->test('pages.app.compte')
        ->set('closurePassword', 'password')->set('closureConfirmed', true)
        ->call('deleteAccount')->assertHasNoErrors()->assertRedirect(route('login'));

    expect(User::find($client->id))->toBeNull()
        ->and(User::withTrashed()->findOrFail($client->id)->email)->toBe($client->email)
        ->and(User::withTrashed()->findOrFail($client->id)->password)->toBe($password)
        ->and(User::withTrashed()->findOrFail($client->id)->status)->toBe('active')
        ->and(User::withTrashed()->findOrFail($client->id)->hasRole(Role::Client->value))->toBeTrue()
        ->and(session('account_closed'))->toBeTrue();
    foreach ($models as $index => $model) {
        expect($model->fresh()->getRawOriginal())->toBe($snapshots[$index]);
    }
    Notification::assertNothingSent();
    $this->assertGuest();
});

it('refuse une confirmation incomplete ou un mauvais mot de passe sans fermer le compte', function (string $password, bool $confirmed, string $error): void {
    $client = rgpdClient();
    $before = $client->fresh()->getRawOriginal();
    Volt::actingAs($client)->test('pages.app.compte')
        ->set('closurePassword', $password)->set('closureConfirmed', $confirmed)
        ->call('deleteAccount')->assertHasErrors($error)->assertSet('closurePassword', '');
    expect($client->fresh()->getRawOriginal())->toBe($before);
    $this->assertAuthenticatedAs($client);
})->with([['', true, 'closurePassword'], ['incorrect', true, 'closurePassword'], ['password', false, 'closureConfirmed']]);

it('annule la preparation sans modifier le compte et retire le mot de passe du composant', function (): void {
    $client = rgpdClient();
    Volt::actingAs($client)->test('pages.app.compte')->set('closurePassword', 'password')->set('closureConfirmed', true)
        ->call('cancelClosure')->assertSet('closurePassword', '')->assertSet('closureConfirmed', false)->assertHasNoErrors();
    expect(User::find($client->id))->not->toBeNull();
    $this->assertAuthenticatedAs($client);
});

it('conserve le profil actif et les autres comptes lors dune mise a jour', function (): void {
    $client = rgpdClient();
    $other = rgpdClient();
    $otherBefore = $other->fresh()->getRawOriginal();
    Volt::actingAs($client)->test('pages.app.compte')->set('name', 'Profil fictif modifié')->call('save')->assertHasNoErrors();
    expect($client->fresh()->name)->toBe('Profil fictif modifié')
        ->and($client->fresh()->deleted_at)->toBeNull()
        ->and($other->fresh()->getRawOriginal())->toBe($otherBefore);
});

it('ne ferme et ne modifie que le compte authentifie', function (): void {
    $client = rgpdClient();
    $other = rgpdClient();
    $before = $other->fresh()->getRawOriginal();
    Volt::actingAs($client)->test('pages.app.compte')->set('closurePassword', 'password')->set('closureConfirmed', true)
        ->call('deleteAccount')->assertRedirect(route('login'));
    expect($other->fresh()->getRawOriginal())->toBe($before);
});

it('ne revoque pas un appareil appartenant a un autre compte', function (): void {
    $client = rgpdClient();
    $other = rgpdClient();
    $device = TrustedDevice::create(['user_id' => $other->id, 'token' => hash('sha256', Str::random(48)), 'expires_at' => now()->addDay()]);
    Volt::actingAs($client)->test('pages.app.compte')->call('revokeDevice', $device->id);
    expect($device->fresh())->not->toBeNull();
});

it('refuse les identifiants du compte ferme y compris avec son ancien appareil approuve', function (): void {
    $client = rgpdClient();
    $token = Str::random(48);
    TrustedDevice::create(['user_id' => $client->id, 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
    $client->delete();
    Livewire::withCookies(['sz_device_'.hash('sha256', (string) $client->id) => $token]);
    Volt::test('pages.auth.login')->set('email', $client->email)->set('password', 'password')
        ->call('login')->assertHasErrors('email');
    $this->assertGuest();
    expect(Auth::guard('web')->getProvider()->retrieveById($client->id))->toBeNull()
        ->and(Auth::guard('web')->getProvider()->retrieveByToken($client->id, $client->getRememberToken()))->toBeNull();
    Notification::assertNothingSent();
});

it('refuse une autre session database deja ouverte apres fermeture', function (): void {
    config(['session.driver' => 'database']);
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    Auth::forgetGuards();
    $client = rgpdClient();
    $guardName = Auth::guard('web')->getName();
    $first = Str::random(40);
    $other = Str::random(40);
    foreach ([$first, $other] as $id) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $client->id, 'payload' => base64_encode(json_encode([
            $guardName => $client->id, '_token' => Str::random(40),
        ], JSON_THROW_ON_ERROR)), 'last_activity' => now()->timestamp]);
    }
    Auth::forgetGuards();
    $this->withCookie(config('session.cookie'), $other)->get('/app/compte')->assertOk();
    Auth::forgetGuards();
    $this->withCookie(config('session.cookie'), $first)->get('/app/compte')->assertOk();
    Volt::actingAs($client)->test('pages.app.compte')->set('closurePassword', 'password')->set('closureConfirmed', true)
        ->call('deleteAccount')->assertRedirect(route('login'));
    Auth::forgetGuards();
    $this->withCookie(config('session.cookie'), $other)->get('/app/compte')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('affiche la clarification sans reecrire le document CMS ni sa version', function (): void {
    $doc = LegalDocument::where('key', 'privacy')->firstOrFail();
    $doc->update(['body' => '<h2>Contenu éditorial fictif</h2><p>Texte CMS conservé pour examen séparé.</p>']);
    $before = $doc->fresh()->getRawOriginal();
    $this->get('/confidentialite')->assertOk()->assertSee('Fermeture du compte et données conservées')
        ->assertSee('Contenu éditorial fictif')->assertSee('Contacter Tikmos pour cette demande');
    expect($doc->fresh()->getRawOriginal())->toBe($before);
});

it('continue a refuser une politique depubliee sans creer de document par defaut', function (): void {
    $doc = LegalDocument::where('key', 'privacy')->firstOrFail();
    $doc->update(['is_published' => false]);
    $before = $doc->fresh()->getRawOriginal();
    $this->get('/confidentialite')->assertNotFound();
    expect($doc->fresh()->getRawOriginal())->toBe($before);
});

it('exporte les donnees du compte courant sans inclure celles dun autre client', function (): void {
    $client = rgpdClient();
    $other = rgpdClient();
    $mine = ServiceRequest::factory()->create(['user_id' => $client->id]);
    $foreign = ServiceRequest::factory()->create(['user_id' => $other->id]);
    $orders = [];
    foreach ([$client, $other] as $index => $owner) {
        $orders[] = Order::create(['number' => 'F11-EXPORT-'.$index, 'user_id' => $owner->id,
            'subtotal' => 10000, 'total' => 10000, 'contact_name' => 'Client fictif',
            'contact_phone' => 'Fictif', 'delivery_address' => 'Adresse fictive']);
    }
    $page = Volt::actingAs($client)->test('pages.app.compte')->call('exportData')
        ->assertFileDownloaded('mes-donnees-tikmos.json');
    $json = json_decode(base64_decode($page->effects['download']['content']), true, 512, JSON_THROW_ON_ERROR);
    expect($json['profil']['email'])->toBe($client->email)
        ->and(array_column($json['demandes'], 'reference'))->toBe([$mine->reference])
        ->and(array_column($json['commandes'], 'numero'))->toBe([$orders[0]->number])
        ->and(json_encode($json))->not->toContain($other->email, $foreign->reference, $orders[1]->number);
});
