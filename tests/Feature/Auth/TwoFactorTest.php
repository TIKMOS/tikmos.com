<?php

use App\Enums\Role;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
    $this->seed(RolePermissionSeeder::class);
});

function wrongTwoFactorCode(string $code): string
{
    return $code === '000000' ? '111111' : '000000';
}

it('F10 refuse un renvoi direct immediat sans notification supplementaire', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    app(TwoFactorService::class)->sendCode($user);
    session(['2fa.user_id' => $user->id]);

    Volt::test('pages.auth.two-factor-challenge')
        ->call('resend')
        ->assertHasErrors('resend');

    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
});

it('F10 ne reinitialise pas les essais apres un renvoi', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';
    session(['2fa.user_id' => $user->id]);
    $challenge = Volt::test('pages.auth.two-factor-challenge');
    for ($i = 0; $i < 3; $i++) {
        $challenge->set('code', $wrong)->call('verify')->assertHasErrors('code');
    }
    $this->travel(60)->seconds();

    $challenge->call('resend')->assertHasErrors('resend');
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
    $this->assertGuest();
});

it('F10 laisse la confiance desactivee par defaut', function (): void {
    $user = makeUser(Role::Admin->value);
    session(['2fa.user_id' => $user->id]);

    Volt::test('pages.auth.two-factor-challenge')->assertSet('remember_device', false);
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
    $this->app->detectEnvironment(fn () => 'local');
    config(['cache.default' => 'database']);
    session(['2fa.user_id' => $user->id, '2fa.dev_code' => '424242']);

    Volt::test('pages.auth.two-factor-challenge')
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

it('accepte le code une seule fois sans creer de confiance implicite', function (): void {
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    session(['2fa.user_id' => $user->id, '2fa.dev_code' => 'obsolete']);

    Volt::test('pages.auth.two-factor-challenge')
        ->set('code', $code)->call('verify')->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
    expect(TrustedDevice::where('user_id', $user->id)->count())->toBe(0)
        ->and(session('2fa.user_id'))->toBeNull()
        ->and(session('2fa.dev_code'))->toBeNull();
    expect(fn () => $service->verify($user, $code))->toThrow(ValidationException::class, 'expiré');
});

it('accepte avant expiration et refuse exactement a expiration', function (int $seconds, bool $valid): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    $this->travel($seconds)->seconds();

    if ($valid) {
        expect($service->verify($user, $code))->toBeTrue();
    } else {
        expect(fn () => $service->verify($user, $code))->toThrow(ValidationException::class, 'expiré');
        expect($service->delays($user)['verify'])->toBe(0);
    }
})->with([[599, true], [600, false], [601, false]]);

it('ne prolonge pas le delai quand un renvoi est refuse', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    $this->travel(59)->seconds();

    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, '1 secondes');
    expect($service->delays($user)['resend'])->toBe(1);
    expect($service->verify($user, $code))->toBeTrue();
    $this->travel(1)->second();
    $service->sendCode($user);
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 2);
});

it('remplace le code et efface les anciennes erreurs uniquement apres un renvoi reussi', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $old = $service->sendCode($user);
    session(['2fa.user_id' => $user->id]);
    $challenge = Volt::test('pages.auth.two-factor-challenge')
        ->set('code', wrongTwoFactorCode($old))->call('verify')
        ->assertHasErrors('code')->assertSee('Code incorrect');
    $challenge->call('resend')->assertHasErrors('resend');
    $this->travel(60)->seconds();
    $challenge->call('resend')->assertHasNoErrors()->assertSet('code', '')
        ->assertDispatched('otp-delays', resend: 60, verify: 0)
        ->assertSee('Un nouveau code a été envoyé');
    $new = Notification::sent($user, TwoFactorCodeNotification::class)->last()->code;

    expect($new)->not->toBe($old);
    expect(fn () => $service->verify($user, $old))->toThrow(ValidationException::class, 'Code incorrect');
    expect($service->verify($user, $new))->toBeTrue();
});

it('limite tous les envois du compte sur une fenetre fixe', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    for ($i = 0; $i < 5; $i++) {
        if ($i > 0) {
            $this->travel(60)->seconds();
        }
        $service->sendCode($user);
    }
    $this->travel(60)->seconds();
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, '600 secondes');
    $this->travel(599)->seconds();
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, '1 secondes');
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 5);
    $this->travel(1)->second();
    $service->sendCode($user);
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 6);
});

it('conserve les erreurs cumulees entre deux codes et deux connexions', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value, ['email' => 'otp@example.test', 'password' => bcrypt('test-password-only')]);
    $service = app(TwoFactorService::class);
    $first = $service->sendCode($user);
    expect(fn () => $service->verify($user, wrongTwoFactorCode($first)))->toThrow(ValidationException::class, '2 tentative');
    $this->travel(60)->seconds();
    $second = $service->sendCode($user);
    expect(fn () => $service->verify($user, wrongTwoFactorCode($second)))->toThrow(ValidationException::class, '1 tentative');
    session()->forget('2fa');
    Volt::test('pages.auth.login')->set('email', $user->email)->set('password', 'test-password-only')
        ->call('login')->assertRedirect(route('two-factor.challenge'));
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 2);
    Volt::test('pages.auth.two-factor-challenge')->set('code', wrongTwoFactorCode($second))
        ->call('verify')->assertHasErrors('code')->assertSee('Validation temporairement bloquée');
    expect(fn () => $service->verify($user, $second))->toThrow(ValidationException::class, 'bloquée');
    $this->assertGuest();
});

it('reprend apres le blocage sans prolongation par les tentatives refusees', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $old = $service->sendCode($user);
    for ($i = 0; $i < 3; $i++) {
        expect(fn () => $service->verify($user, wrongTwoFactorCode($old)))->toThrow(ValidationException::class);
    }
    $this->travel(899)->seconds();
    expect(fn () => $service->verify($user, $old))->toThrow(ValidationException::class, '1 secondes');
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, '1 secondes');
    $this->travel(1)->second();
    $new = $service->sendCode($user);
    expect($service->verify($user, $new))->toBeTrue();
});

it('conserve les quotas apres une verification reussie', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    expect(fn () => $service->verify($user, wrongTwoFactorCode($code)))->toThrow(ValidationException::class);
    expect($service->verify($user, $code))->toBeTrue();
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class);
    $this->travel(60)->seconds();
    $new = $service->sendCode($user);
    expect(fn () => $service->verify($user, wrongTwoFactorCode($new)))->toThrow(ValidationException::class, '1 tentative');
});

it('isole les quotas de deux comptes partageant une adresse IP', function (): void {
    $first = makeUser(Role::Admin->value);
    $second = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $a = $service->sendCode($first);
    for ($i = 0; $i < 3; $i++) {
        expect(fn () => $service->verify($first, wrongTwoFactorCode($a)))->toThrow(ValidationException::class);
    }
    $b = $service->sendCode($second);
    expect($service->verify($second, $b))->toBeTrue();
    expect(fn () => $service->sendCode($first))->toThrow(ValidationException::class);
});

it('refuse les actions quand la session de challenge a disparu', function (string $action): void {
    $user = makeUser(Role::Admin->value);
    session(['2fa.user_id' => $user->id]);
    $challenge = Volt::test('pages.auth.two-factor-challenge');
    session()->forget('2fa');
    $challenge->set('code', '123456')->call($action)->assertRedirect(route('login'));
    Notification::assertNothingSent();
    $this->assertGuest();
})->with(['verify', 'resend']);

it('refuse un challenge pour un compte suspendu ou devenu client', function (bool $suspended): void {
    $user = makeUser($suspended ? Role::Admin->value : Role::Client->value);
    if ($suspended) {
        $user->suspend();
    }
    session(['2fa.user_id' => $user->id]);
    Volt::test('pages.auth.two-factor-challenge')->set('code', '123456')
        ->call('verify')->assertRedirect(route('login'));
    $this->assertGuest();
})->with([true, false]);

it('cache le code dev et permet la connexion OTP en production', function (): void {
    $user = makeUser(Role::Admin->value);
    config(['cache.default' => 'database', 'tikmos.two_factor.dev_show_code' => true]);
    $this->app->detectEnvironment(fn () => 'production');
    $code = app(TwoFactorService::class)->sendCode($user);
    session(['2fa.user_id' => $user->id, '2fa.dev_code' => $code]);

    Volt::test('pages.auth.two-factor-challenge')->assertSet('devCode', null)
        ->assertDontSee('Code de connexion :')->set('code', $code)->call('verify')
        ->assertRedirect('/admin');
    expect(session('2fa.dev_code'))->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('ne peut pas injecter une valeur dev depuis Livewire', function (): void {
    $user = makeUser(Role::Admin->value);
    session(['2fa.user_id' => $user->id]);
    expect(fn () => Volt::test('pages.auth.two-factor-challenge')->set('devCode', '123456'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('ne permet pas de modifier les delais serveur depuis Livewire', function (string $field): void {
    $user = makeUser(Role::Admin->value);
    session(['2fa.user_id' => $user->id]);
    expect(fn () => Volt::test('pages.auth.two-factor-challenge')->set($field, 0))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with(['resendDelay', 'verifyDelay']);

it('preserve le login depuis un appareil deja approuve', function (bool $expired): void {
    $user = makeUser(Role::Admin->value, ['email' => 'trusted@example.test', 'password' => bcrypt('test-password-only')]);
    $token = Str::random(48);
    TrustedDevice::create(['user_id' => $user->id, 'token' => hash('sha256', $token), 'expires_at' => $expired ? now()->subSecond() : now()->addDay()]);
    Livewire::withCookies(['sz_device_'.hash('sha256', (string) $user->id) => $token]);
    Volt::test('pages.auth.login')->set('email', $user->email)->set('password', 'test-password-only')
        ->call('login')->assertRedirect($expired ? route('two-factor.challenge') : '/admin');
    if ($expired) {
        $this->assertGuest();
        Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
    } else {
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }
    expect(TrustedDevice::where('user_id', $user->id)->count())->toBe(1);
})->with([true, false]);

it('retrouve les quotas apres recreation de ladaptateur database SQLite', function (): void {
    $this->freezeTime();
    config(['cache.default' => 'database']);
    $user = makeUser(Role::Admin->value);
    $first = app(TwoFactorService::class);
    $code = $first->sendCode($user);
    expect(fn () => $first->verify($user, wrongTwoFactorCode($code)))->toThrow(ValidationException::class);
    Cache::forgetDriver('database'); // Recrée l'adaptateur ; ne supprime aucune donnée de cache.
    $second = new TwoFactorService;
    expect(fn () => $second->sendCode($user))->toThrow(ValidationException::class);
    expect(fn () => $second->verify($user, wrongTwoFactorCode($code)))->toThrow(ValidationException::class, '1 tentative');
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
});

it('echoue sans authentification si le cache nest pas persistant', function (string $driver): void {
    $user = makeUser(Role::Admin->value);
    config(['cache.default' => 'unavailable', 'cache.stores.unavailable.driver' => $driver]);
    Log::spy();
    $service = app(TwoFactorService::class);
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, 'momentanément indisponible');
    expect(fn () => $service->verify($user, '123456'))->toThrow(ValidationException::class, 'momentanément indisponible');
    Notification::assertNothingSent();
    $this->assertGuest();
    Log::shouldHaveReceived('warning')->with('two_factor_unavailable', ['exception_type' => RuntimeException::class])->twice();
})->with(['null', 'failover']);

it('refuse une action concurrente sans envoyer ni consommer de code', function (): void {
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $code = $service->sendCode($user);
    $key = '2fa:v2:'.hash_hmac('sha256', (string) $user->id, (string) config('app.key')).':lock';
    $lock = Cache::lock($key, 60);
    expect($lock->get())->toBeTrue();
    try {
        expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, '2 secondes');
        expect(fn () => $service->verify($user, $code))->toThrow(ValidationException::class, '2 secondes');
    } finally {
        $lock->release();
    }
    expect($service->verify($user, $code))->toBeTrue();
    Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
});

it('preserve le code precedent si le transport du renvoi echoue', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $old = $service->sendCode($user);
    $this->travel(60)->seconds();
    $failing = Mockery::mock($user)->makePartial();
    $failing->shouldReceive('notify')->once()->andThrow(new RuntimeException('transport indisponible'));
    Log::spy();
    expect(fn () => $service->sendCode($failing))->toThrow(ValidationException::class, 'momentanément indisponible');
    expect($service->delays($user)['resend'])->toBe(60);
    expect($service->verify($user, $old))->toBeTrue();
    Log::shouldHaveReceived('warning')->with('two_factor_unavailable', ['exception_type' => RuntimeException::class])->once();
});

it('necrase pas un code et des quotas plus recents apres perte du bail denvoi', function (): void {
    $this->freezeTime();
    $user = makeUser(Role::Admin->value);
    $service = app(TwoFactorService::class);
    $service->sendCode($user);
    $this->travel(60)->seconds();
    $newCode = null;
    $slow = Mockery::mock($user)->makePartial();
    $slow->shouldReceive('notify')->once()->andReturnUsing(function () use ($user, $service, &$newCode): void {
        $this->travel(60)->seconds();
        $newCode = $service->sendCode($user);
    });
    Log::spy();
    expect(fn () => $service->sendCode($slow))->toThrow(ValidationException::class, 'momentanément indisponible');
    expect($newCode)->toBeString();
    expect($service->verify($user, $newCode))->toBeTrue();
    for ($i = 0; $i < 2; $i++) {
        $this->travel(60)->seconds();
        $service->sendCode($user);
    }
    $this->travel(60)->seconds();
    expect(fn () => $service->sendCode($user))->toThrow(ValidationException::class, 'Renvoi temporairement indisponible');
    Log::shouldHaveReceived('warning')->with('two_factor_unavailable', ['exception_type' => RuntimeException::class])->once();
});
