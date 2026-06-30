<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('envoie un lien de réinitialisation', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'c@exemple.com']);

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'c@exemple.com')
        ->call('sendLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('réinitialise le mot de passe avec un jeton valide', function (): void {
    $user = User::factory()->create(['email' => 'c@exemple.com']);
    $token = Password::createToken($user);

    Volt::test('pages.auth.reset-password', ['token' => $token])
        ->set('email', 'c@exemple.com')
        ->set('password', 'nouveau-pass-123')
        ->set('password_confirmation', 'nouveau-pass-123')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('nouveau-pass-123', $user->fresh()->password))->toBeTrue();
});

it('refuse un jeton invalide', function (): void {
    User::factory()->create(['email' => 'c@exemple.com']);

    Volt::test('pages.auth.reset-password', ['token' => 'jeton-bidon'])
        ->set('email', 'c@exemple.com')
        ->set('password', 'nouveau-pass-123')
        ->set('password_confirmation', 'nouveau-pass-123')
        ->call('resetPassword')
        ->assertHasErrors('email');
});
