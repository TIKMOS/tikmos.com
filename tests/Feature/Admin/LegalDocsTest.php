<?php

use App\Enums\Role;
use App\Models\LegalDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function adminU(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('affiche les CGU avec sommaire et contenu', function (): void {
    $this->get('/cgu')->assertOk()
        ->assertSee('Conditions générales')
        ->assertSee('Sommaire')
        ->assertSee('Objet')
        ->assertSee('Devis &amp; prestations', false);
});

it('génère des ancres sur les titres', function (): void {
    $doc = LegalDocument::where('key', 'cgu')->first();
    $parsed = $doc->parsed();

    expect($parsed['toc'])->toHaveCount(5)
        ->and($parsed['html'])->toContain('id="'.$parsed['toc'][0]['id'].'"');
});

it('renvoie 404 si le document est dépublié', function (): void {
    LegalDocument::where('key', 'privacy')->update(['is_published' => false]);

    $this->get('/confidentialite')->assertNotFound();
});

it('ouvre l’admin Pages et édite avec sanitisation + version', function (): void {
    $this->actingAs(adminU())->get('/admin/reglages/pages')->assertOk();

    $doc = LegalDocument::where('key', 'cgu')->first();
    $before = $doc->version;

    Volt::actingAs(adminU())->test('pages.admin.reglages.pages')
        ->call('edit', $doc)
        ->set('title', 'CGU mises à jour')
        ->set('body', '<h2>Nouveau</h2><p>Texte</p><script>alert(1)</script>')
        ->call('save')
        ->assertHasNoErrors();

    $doc->refresh();
    expect($doc->title)->toBe('CGU mises à jour')
        ->and($doc->body)->not->toContain('<script>')
        ->and($doc->version)->toBe($before + 1);
});

it('refuse l’admin Pages sans permission', function (): void {
    $agent = User::factory()->create();
    $agent->assignRole(Role::AgentCotonou->value);
    $this->actingAs($agent)->get('/admin/reglages/pages')->assertForbidden();
});
