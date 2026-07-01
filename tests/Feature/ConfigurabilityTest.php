<?php

use App\Enums\Role;
use App\Models\User;
use App\Settings\FeatureSettings;
use App\Settings\ShopSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function cfgAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('sert le blog quand la fonctionnalité est active', function (): void {
    $f = app(FeatureSettings::class);
    $f->blog = true;
    $f->save();

    $this->get('/blog')->assertOk();
});

it('renvoie 404 sur le blog quand la fonctionnalité est désactivée', function (): void {
    $f = app(FeatureSettings::class);
    $f->blog = false;
    $f->save();

    $this->get('/blog')->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('href="'.url('/blog').'"', false);
});

it('n\'active le service worker (méta PWA) que si la PWA est activée', function (): void {
    $f = app(FeatureSettings::class);

    $f->pwa = false;
    $f->save();
    $this->get('/')->assertOk()->assertDontSee('name="tk-pwa"', false);

    $f->pwa = true;
    $f->save();
    $this->get('/')->assertOk()->assertSee('name="tk-pwa"', false);
});

it('masque la boutique quand elle est désactivée', function (): void {
    $s = app(ShopSettings::class);
    $s->enabled = false;
    $s->save();

    $this->get('/boutique')->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('href="'.url('/boutique').'"', false);
});

it('permet de piloter les fonctionnalités depuis l\'admin', function (): void {
    Volt::actingAs(cfgAdmin())->test('pages.admin.reglages.features')
        ->set('blog', false)
        ->set('pwa', true)
        ->set('shop', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(FeatureSettings::class)->blog)->toBeFalse()
        ->and(app(FeatureSettings::class)->pwa)->toBeTrue()
        ->and(app(ShopSettings::class)->enabled)->toBeFalse();
});

it('réserve l\'écran fonctionnalités à la permission settings.manage', function (): void {
    $client = User::factory()->create(['status' => 'active']);
    $client->assignRole(Role::Client->value);

    $this->actingAs($client)->get('/admin/reglages/fonctionnalites')->assertForbidden();
});
