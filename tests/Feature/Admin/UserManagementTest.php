<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function superAdmin(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::SuperAdmin->value);

    return $u;
}

it('crée un membre du personnel et envoie l’invitation', function (): void {
    Notification::fake();

    Volt::actingAs(superAdmin())->test('pages.admin.equipe')
        ->call('openCreate')
        ->set('fName', 'Nouveau Staff')
        ->set('fEmail', 'staff@sezy.test')
        ->set('fRole', Role::Support->value)
        ->set('fMode', 'email')
        ->call('save')
        ->assertHasNoErrors();

    $invited = User::where('email', 'staff@sezy.test')->first();
    expect($invited)->not->toBeNull()
        ->and($invited->hasRole(Role::Support->value))->toBeTrue()
        ->and($invited->invited_at)->not->toBeNull();
    Notification::assertSentTo($invited, StaffInvitation::class);
});

it('crée un membre avec mot de passe défini (sans email)', function (): void {
    Notification::fake();

    Volt::actingAs(superAdmin())->test('pages.admin.equipe')
        ->call('openCreate')
        ->set('fName', 'Staff PW')
        ->set('fEmail', 'staffpw@sezy.test')
        ->set('fRole', Role::Support->value)
        ->set('fMode', 'password')
        ->set('fPassword', 'motdepasse123')
        ->call('save')
        ->assertHasNoErrors();

    $u = User::where('email', 'staffpw@sezy.test')->first();
    expect($u)->not->toBeNull()
        ->and($u->invited_at)->toBeNull()
        ->and(Hash::check('motdepasse123', $u->password))->toBeTrue();
    Notification::assertNothingSent();
});

it('modifie un membre du personnel (nom, email, rôle)', function (): void {
    $target = User::factory()->create(['status' => 'active', 'name' => 'Ancien']);
    $target->assignRole(Role::Support->value);

    Volt::actingAs(superAdmin())->test('pages.admin.equipe')
        ->call('openEdit', $target)
        ->set('fName', 'Renommé')
        ->set('fRole', Role::AgentCotonou->value)
        ->call('save')
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->name)->toBe('Renommé')
        ->and($target->hasRole(Role::AgentCotonou->value))->toBeTrue();
});

it('suspend un membre et révoque sa 2FA', function (): void {
    $admin = superAdmin();
    $target = User::factory()->create(['status' => 'active', 'two_factor_confirmed_at' => now()]);
    $target->assignRole(Role::Support->value);

    Volt::actingAs($admin)->test('pages.admin.equipe')
        ->call('toggleSuspend', $target);
    expect($target->fresh()->isSuspended())->toBeTrue();

    Volt::actingAs($admin)->test('pages.admin.equipe')
        ->call('resetTwoFactor', $target);
    expect($target->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('empêche de rétrograder le dernier super administrateur', function (): void {
    $admin = superAdmin();

    Volt::actingAs($admin)->test('pages.admin.equipe')
        ->call('changeRole', $admin, Role::Support->value);

    expect($admin->fresh()->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

it('refuse la gestion du personnel sans permission', function (): void {
    $agent = User::factory()->create();
    $agent->assignRole(Role::AgentCotonou->value);

    Volt::actingAs($agent)->test('pages.admin.equipe')
        ->set('fName', 'X')->set('fEmail', 'x@y.test')->set('fRole', Role::Support->value)
        ->call('save')
        ->assertForbidden();
});

it('crée un client depuis l’admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Volt::actingAs($admin)->test('pages.admin.clients')
        ->call('openCreate')
        ->set('cName', 'Client Test')
        ->set('cEmail', 'newclient@sezy.test')
        ->set('cPhone', '+229 90 00 00 00')
        ->set('cCity', 'Cotonou')
        ->set('cPassword', 'motdepasse123')
        ->call('saveClient')
        ->assertHasNoErrors();

    $c = User::where('email', 'newclient@sezy.test')->first();
    expect($c)->not->toBeNull()
        ->and($c->hasRole(Role::Client->value))->toBeTrue()
        ->and($c->city)->toBe('Cotonou');
});

it('modifie un client : note interne, infos et suspension', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $client = User::factory()->create(['status' => 'active', 'name' => 'Avant']);
    $client->assignRole(Role::Client->value);

    Volt::actingAs($admin)->test('pages.admin.clients')
        ->call('open', $client->id)
        ->set('notes', 'Client VIP — prioriser.')
        ->call('saveNotes')
        ->call('openEdit')
        ->set('cName', 'Après')
        ->call('saveClient')
        ->call('open', $client->id)
        ->call('toggleSuspend');

    $client->refresh();
    expect($client->internal_notes)->toBe('Client VIP — prioriser.')
        ->and($client->name)->toBe('Après')
        ->and($client->isSuspended())->toBeTrue();
});
