<?php

use App\Enums\Role;
use App\Models\FaqItem;
use App\Models\User;
use App\Settings\HomepageSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function adminUser(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('reflète le titre du hero configuré', function (): void {
    $s = app(HomepageSettings::class);
    $s->hero_title = 'Titre hero unique 7777';
    $s->save();

    $this->get('/')->assertOk()->assertSee('Titre hero unique 7777');
});

it('masque une section désactivée', function (): void {
    $s = app(HomepageSettings::class);
    $s->sections_enabled = array_merge($s->sections_enabled, ['services' => false]);
    $s->save();

    $this->get('/')->assertOk()->assertDontSee("Tout pour rapprocher l'Europe et l'Afrique.");
});

it('affiche les FAQ publiées sur la page FAQ', function (): void {
    FaqItem::query()->delete();
    FaqItem::create(['question' => 'Question test unique 31', 'answer' => '<p>Réponse</p>', 'position' => 0, 'is_published' => true]);
    FaqItem::create(['question' => 'Brouillon caché 32', 'answer' => '<p>x</p>', 'position' => 1, 'is_published' => false]);

    $this->get('/faq')->assertOk()->assertSee('Question test unique 31')->assertDontSee('Brouillon caché 32');
});

it('ouvre l’admin FAQ et crée une question sanitizée', function (): void {
    $this->actingAs(adminUser())->get('/admin/reglages/faq')->assertOk();

    Volt::actingAs(adminUser())->test('pages.admin.reglages.faq')
        ->call('create')
        ->set('question', 'Une question 99')
        ->set('answer', '<p>Bonjour</p><script>alert(1)</script>')
        ->call('save')
        ->assertHasNoErrors();

    $item = FaqItem::where('question', 'Une question 99')->first();
    expect($item)->not->toBeNull()
        ->and($item->answer)->not->toContain('<script>');
});

it('ouvre l’admin Accueil pour un admin et refuse sinon', function (): void {
    $this->actingAs(adminUser())->get('/admin/reglages/accueil')->assertOk();

    $agent = User::factory()->create();
    $agent->assignRole(Role::Support->value);
    $this->actingAs($agent)->get('/admin/reglages/accueil')->assertForbidden();
});
