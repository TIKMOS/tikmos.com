<?php

use App\Enums\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('exige un code 2FA pour le staff', function (): void {
    Notification::fake();
    makeUser(Role::Admin->value, ['email' => 'staff@tikmos.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'staff@tikmos.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
    expect(session('2fa.user_id'))->not->toBeNull();
    Notification::assertSentTo(User::where('email', 'staff@tikmos.com')->first(), TwoFactorCodeNotification::class);
});

it('ne demande pas de 2FA à un client', function (): void {
    makeUser(Role::Client->value, ['email' => 'c@exemple.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'c@exemple.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect('/app');

    $this->assertAuthenticated();
});

it('valide le challenge avec le bon code et mémorise l’appareil', function (): void {
    $user = makeUser(Role::Admin->value);
    $code = app(TwoFactorService::class)->sendCode($user);
    session(['2fa.user_id' => $user->id, '2fa.remember' => false]);

    Volt::test('pages.auth.two-factor-challenge')
        ->set('code', $code)
        ->set('remember_device', true)
        ->call('verify')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
    expect(TrustedDevice::where('user_id', $user->id)->count())->toBe(1);
});

it('rejette un mauvais code', function (): void {
    $user = makeUser(Role::Admin->value);
    $code = app(TwoFactorService::class)->sendCode($user);
    session(['2fa.user_id' => $user->id, '2fa.remember' => false]);

    Volt::test('pages.auth.two-factor-challenge')
        ->set('code', $code === '000000' ? '111111' : '000000')
        ->call('verify')
        ->assertHasErrors('code');

    $this->assertGuest();
});

it('reconnaît un appareil de confiance', function (): void {
    $user = makeUser(Role::Admin->value);
    $token = Str::random(48);
    TrustedDevice::create([
        'user_id' => $user->id,
        'token' => hash('sha256', $token),
        'expires_at' => now()->addDays(30),
    ]);

    $request = Request::create('/', 'GET');
    $request->cookies->set('sz_device_'.hash('sha256', (string) $user->id), $token);

    expect(app(TwoFactorService::class)->isDeviceTrusted($user, $request))->toBeTrue();
});

it('journalise une connexion réussie', function (): void {
    makeUser(Role::Client->value, ['email' => 'c@exemple.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'c@exemple.com')
        ->set('password', 'secret123')
        ->call('login');

    $this->assertDatabaseHas('login_logs', ['email' => 'c@exemple.com', 'action' => 'login']);
});

it('n’expose le code dev qu’en environnement local', function (): void {
    expect(app(TwoFactorService::class)->showsDevCode())->toBeFalse(); // env = testing

    $this->app->detectEnvironment(fn () => 'local');
    expect(app(TwoFactorService::class)->showsDevCode())->toBeTrue();

    config(['tikmos.two_factor.dev_show_code' => false]);
    expect(app(TwoFactorService::class)->showsDevCode())->toBeFalse();
});

it('affiche le code sur le challenge en mode dev', function (): void {
    $user = makeUser(Role::Admin->value);
    session(['2fa.user_id' => $user->id]);

    Volt::test('pages.auth.two-factor-challenge')
        ->set('devCode', '424242')
        ->assertSee('424242');
});

it('journalise un échec de connexion', function (): void {
    makeUser(Role::Client->value, ['email' => 'c@exemple.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'c@exemple.com')
        ->set('password', 'mauvais')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertDatabaseHas('login_logs', ['email' => 'c@exemple.com', 'action' => 'failed']);
});
