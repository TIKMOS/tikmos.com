<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function makeUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

it('affiche la page de connexion', function (): void {
    $this->get('/connexion')->assertOk()->assertSee('Connexion');
});

it('affiche la page d’inscription', function (): void {
    $this->get('/inscription')->assertOk()->assertSee('Créer un compte');
});

it('inscrit un nouveau client et le connecte', function (): void {
    Volt::test('pages.auth.register')
        ->set('name', 'Jean Diaspora')
        ->set('email', 'jean@exemple.com')
        ->set('phone', '+22990000000')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('/app');

    $user = User::where('email', 'jean@exemple.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole(Role::Client->value))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('connecte un client avec de bons identifiants vers /app', function (): void {
    $user = makeUser(Role::Client->value, ['email' => 'c@exemple.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'c@exemple.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/app');

    $this->assertAuthenticatedAs($user);
});

it('connecte un membre du staff vers /admin quand le 2FA est désactivé', function (): void {
    config(['tikmos.two_factor.enabled' => false]);
    makeUser(Role::Admin->value, ['email' => 'staff@tikmos.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'staff@tikmos.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect('/admin');
});

it('rejette de mauvais identifiants', function (): void {
    makeUser(Role::Client->value, ['email' => 'c@exemple.com', 'password' => bcrypt('secret123')]);

    Volt::test('pages.auth.login')
        ->set('email', 'c@exemple.com')
        ->set('password', 'mauvais')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('déconnecte l’utilisateur', function (): void {
    $user = makeUser(Role::Client->value);

    $this->actingAs($user)->post('/deconnexion')->assertRedirect('/');
    $this->assertGuest();
});
