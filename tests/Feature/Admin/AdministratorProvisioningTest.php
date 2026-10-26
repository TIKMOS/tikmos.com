<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role as PermissionRole;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    // Ces variables sont retirées uniquement du processus de test, jamais du fichier .env.
    foreach (['TIKMOS_ADMIN_NAME', 'TIKMOS_ADMIN_EMAIL', 'TIKMOS_ADMIN_PASSWORD'] as $name) {
        Env::getRepository()->clear($name);
    }
    config(['admin.name' => null, 'admin.email' => null, 'admin.password' => null]);
    $this->seed(RolePermissionSeeder::class);
});

/** @param array<string, mixed> $overrides */
function initialAdminConfiguration(array $overrides = []): string
{
    $password = 'Aa9!'.bin2hex(random_bytes(16));
    config(['admin' => array_replace([
        'name' => 'Administrateur fictif',
        'email' => 'initial-admin@example.test',
        'password' => $password,
    ], $overrides)]);

    return $password;
}

it('ne crée aucun compte privilégié lors du seeding courant sans configuration', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0);
    Notification::assertNothingSent();
});

it('ne provisionne rien implicitement même si les variables administrateur sont configurées', function (): void {
    initialAdminConfiguration();
    $this->artisan('db:seed')->assertSuccessful();
    expect(User::count())->toBe(0);
});

it('refuse un appel explicite sans configuration avec un message utile', function (): void {
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'TIKMOS_ADMIN_NAME, TIKMOS_ADMIN_EMAIL et TIKMOS_ADMIN_PASSWORD');
    expect(User::count())->toBe(0);
});

it('refuse une configuration incomplète ou invalide sans créer de compte', function (string $field, mixed $value): void {
    initialAdminConfiguration([$field => $value]);
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class);
    expect(User::count())->toBe(0)->and(DB::table('model_has_roles')->count())->toBe(0);
    Notification::assertNothingSent();
})->with([
    'nom absent' => ['name', null],
    'email absent' => ['email', null],
    'secret absent' => ['password', null],
    'nom vide' => ['name', '   '],
    'email vide' => ['email', ''],
    'secret vide' => ['password', ''],
    'nom non textuel' => ['name', ['incorrect']],
    'email non textuel' => ['email', true],
    'secret non textuel' => ['password', ['incorrect']],
    'nom trop long' => ['name', str_repeat('a', 256)],
    'email invalide' => ['email', 'adresse-invalide'],
    'ancien repli' => ['password', 'password'],
    'sans majuscule' => ['password', 'long-secret-123456789!'],
    'sans minuscule' => ['password', 'LONG-SECRET-123456789!'],
    'sans chiffre' => ['password', 'Long-Secret-Sans-Chiffre!'],
    'sans symbole' => ['password', 'LongSecretSansSymbole123'],
    'trop court' => ['password', 'Aa9!court'],
    'trop long en octets' => ['password', 'Aa9!'.str_repeat('a', 69)],
    'multioctet trop long' => ['password', 'Aa9!'.str_repeat('é', 35)],
    'octet nul' => ['password', "Aa9!LongSecretAvec\0OctetNul"],
]);

it('demonstration F06 : provisionne une seule fois et conserve le secret lors de la répétition', function (): void {
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class);
    $this->artisan('db:seed')->assertSuccessful();
    expect(User::count())->toBe(0);

    $password = initialAdminConfiguration();
    $this->artisan('db:seed', ['--class' => AdminSeeder::class])
        ->expectsOutputToContain('Administrateur initial créé')
        ->assertSuccessful();
    $admin = User::sole();
    $snapshot = $admin->getRawOriginal();
    expect($admin->hasRole(Role::SuperAdmin->value))->toBeTrue()
        ->and(Hash::check($password, $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->toBeNull()
        ->and($admin->two_factor_confirmed_at)->toBeNull()
        ->and($admin->trustedDevices()->count())->toBe(0);

    $newPassword = initialAdminConfiguration(['name' => 'Nom non appliqué']);
    $this->artisan('db:seed', ['--class' => AdminSeeder::class])
        ->expectsOutputToContain('compte, mot de passe, statut et rôles inchangés')
        ->assertSuccessful();
    expect(User::count())->toBe(1)
        ->and($admin->fresh()->getRawOriginal())->toEqual($snapshot)
        ->and(Hash::check($password, $admin->fresh()->password))->toBeTrue()
        ->and(Hash::check($newPassword, $admin->fresh()->password))->toBeFalse()
        ->and(DB::table('model_has_roles')->count())->toBe(1);
    Notification::assertNothingSent();
});

it('normalise les coordonnées et stocke un hachage du mot de passe', function (): void {
    $password = initialAdminConfiguration(['name' => '  Administrateur fictif  ', 'email' => '  INITIAL-ADMIN@EXAMPLE.TEST  ']);
    $this->seed(AdminSeeder::class);
    $admin = User::sole();
    expect($admin->name)->toBe('Administrateur fictif')->and($admin->email)->toBe('initial-admin@example.test')
        ->and($admin->password)->not->toBe($password)->and(Hash::check($password, $admin->password))->toBeTrue();
});

it('accepte un secret multioctet à la limite de 72 octets sans troncature', function (): void {
    $password = 'Aa9!'.str_repeat('é', 34);
    initialAdminConfiguration(['password' => $password]);
    $this->seed(AdminSeeder::class);
    expect(strlen($password))->toBe(72)->and(Hash::check($password, User::sole()->password))->toBeTrue();
});

it('ne révèle ni secret ni hachage dans la sortie du provisionnement ou les erreurs de configuration', function (): void {
    $password = initialAdminConfiguration();
    $status = Artisan::call('db:seed', ['--class' => AdminSeeder::class]);
    expect($status)->toBe(0)->and(Artisan::output())->toContain('Administrateur initial créé')
        ->not->toContain($password, User::sole()->password);

    config(['admin.email' => 'email-invalide']);
    $error = null;
    try {
        Artisan::call('db:seed', ['--class' => AdminSeeder::class]);
    } catch (RuntimeException $exception) {
        $error = $exception;
    }
    expect($error)->toBeInstanceOf(RuntimeException::class)
        ->and($error?->getMessage())->not->toContain($password)
        ->and(Artisan::output())->not->toContain($password);
});

it('conserve intégralement un SuperAdmin existant y compris suspendu et ses rôles supplémentaires', function (string $status): void {
    $admin = User::factory()->create([
        'email' => 'initial-admin@example.test', 'status' => $status,
        'two_factor_confirmed_at' => now()->subDay(), 'internal_notes' => 'Note de test conservée',
    ]);
    $admin->assignRole([Role::SuperAdmin->value, Role::Support->value]);
    $snapshot = $admin->fresh()->getRawOriginal();
    $roles = $admin->roles()->orderBy('id')->pluck('id')->all();
    initialAdminConfiguration();
    $this->seed(AdminSeeder::class);
    expect($admin->fresh()->getRawOriginal())->toEqual($snapshot)
        ->and($admin->roles()->orderBy('id')->pluck('id')->all())->toBe($roles)
        ->and(User::count())->toBe(1);
})->with(['active', 'suspended']);

it('refuse de promouvoir un compte existant sans toucher à son profil ni à ses rôles', function (?string $role): void {
    $account = User::factory()->create(['email' => 'initial-admin@example.test']);
    if ($role !== null) {
        $account->assignRole($role);
    }
    $snapshot = $account->fresh()->getRawOriginal();
    $roles = $account->roles()->pluck('id')->all();
    initialAdminConfiguration(['email' => 'INITIAL-ADMIN@example.test']);
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'Aucune promotion');
    expect($account->fresh()->getRawOriginal())->toEqual($snapshot)
        ->and($account->roles()->pluck('id')->all())->toBe($roles)
        ->and($account->fresh()->hasRole(Role::SuperAdmin->value))->toBeFalse();
})->with([Role::Client->value, Role::Support->value, Role::Admin->value, null]);

it('refuse de restaurer un ancien compte supprimé', function (): void {
    $account = User::factory()->create(['email' => 'initial-admin@example.test']);
    $account->assignRole(Role::SuperAdmin->value);
    $account->delete();
    $snapshot = $account->fresh()->getRawOriginal();
    initialAdminConfiguration();
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'Aucune promotion ni réactivation');
    expect(User::count())->toBe(0)->and(User::withTrashed()->count())->toBe(1)
        ->and(User::withTrashed()->sole()->getRawOriginal())->toEqual($snapshot);
});

it('réserve le provisionnement au premier administrateur et conserve les administrateurs historiques', function (): void {
    $existing = User::factory()->create(['email' => 'autre-admin@example.test']);
    $existing->assignRole(Role::SuperAdmin->value);
    $snapshot = $existing->fresh()->getRawOriginal();
    initialAdminConfiguration();
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'Un SuperAdmin existe déjà');
    expect(User::count())->toBe(1)->and($existing->fresh()->getRawOriginal())->toEqual($snapshot);
});

it('refuse les adresses historiques ambiguës sans choisir un compte arbitrairement', function (): void {
    $first = User::factory()->create(['email' => 'initial-admin@example.test']);
    $first->assignRole(Role::SuperAdmin->value);
    $second = User::factory()->create(['email' => 'INITIAL-ADMIN@example.test']);
    $second->assignRole(Role::Client->value);
    initialAdminConfiguration();
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'Adresse ambiguë');
    expect(User::count())->toBe(2)->and($second->fresh()->hasRole(Role::Client->value))->toBeTrue()
        ->and($second->fresh()->hasRole(Role::SuperAdmin->value))->toBeFalse();
});

it('explique le prérequis des rôles sans créer de compte partiel', function (): void {
    PermissionRole::where('name', Role::SuperAdmin->value)->delete();
    initialAdminConfiguration();
    expect(fn () => $this->seed(AdminSeeder::class))->toThrow(RuntimeException::class, 'RolePermissionSeeder');
    expect(User::count())->toBe(0);
});

it('annule la création si l’affectation du rôle échoue et ne révèle pas le secret dans l’erreur', function (): void {
    $password = initialAdminConfiguration();
    DB::unprepared("CREATE TRIGGER refuse_initial_admin_role BEFORE INSERT ON model_has_roles BEGIN SELECT RAISE(ABORT, 'Role indisponible'); END");
    $error = null;
    try {
        $this->seed(AdminSeeder::class);
    } catch (RuntimeException $exception) {
        $error = $exception;
    }
    expect($error)->toBeInstanceOf(RuntimeException::class)
        ->and($error?->getMessage())->toContain('Provisionnement annulé')->not->toContain($password)
        ->and($error?->getPrevious())->toBeNull()
        ->and(User::count())->toBe(0)->and(DB::table('model_has_roles')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('utilise la configuration chargée même si les variables du processus diffèrent', function (): void {
    $password = initialAdminConfiguration();
    Env::getRepository()->set('TIKMOS_ADMIN_EMAIL', 'inutilise@example.test');
    Env::getRepository()->set('TIKMOS_ADMIN_PASSWORD', 'password');
    $this->seed(AdminSeeder::class);
    expect(User::sole()->email)->toBe('initial-admin@example.test')
        ->and(Hash::check($password, User::sole()->password))->toBeTrue();
});

it('conserve le challenge de double authentification du compte provisionné', function (): void {
    config(['tikmos.two_factor.enabled' => true]);
    $password = initialAdminConfiguration();
    $this->seed(AdminSeeder::class);
    Volt::test('pages.auth.login')->set('email', 'initial-admin@example.test')->set('password', $password)
        ->call('login')->assertHasNoErrors()->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    expect(User::sole()->trustedDevices()->count())->toBe(0);
});
