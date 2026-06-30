<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\Role;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function prjUser(string $role): User
{
    $u = User::factory()->create();
    $u->assignRole($role);

    return $u;
}

// ---------- Réalisations (vitrine) ----------

it('affiche le portfolio des réalisations publiées', function (): void {
    $this->get('/realisations')
        ->assertOk()
        ->assertSee('Réalisations')
        ->assertSee('Plateforme data décisionnelle', false);
});

it('affiche une réalisation publiée et 404 un brouillon', function (): void {
    $published = Project::factory()->create(['title' => 'Cas client visible', 'status' => Publication::Published]);
    $draft = Project::factory()->draft()->create(['title' => 'Cas masqué']);

    $this->get(route('projects.show', $published->slug))->assertOk()->assertSee('Cas client visible');
    $this->get(route('projects.show', $draft->slug))->assertNotFound();
});

it('filtre les réalisations par catégorie', function (): void {
    Project::factory()->create(['title' => 'Projet IA spécial', 'category' => 'Data / IA', 'status' => Publication::Published]);
    Project::factory()->create(['title' => 'Projet web spécial', 'category' => 'Application métier', 'status' => Publication::Published]);

    Volt::test('pages.public.projects-index')
        ->set('category', 'Data / IA')
        ->assertSee('Projet IA spécial')
        ->assertDontSee('Projet web spécial');
});

// ---------- Équipe ----------

it('affiche la page équipe avec les membres actifs', function (): void {
    $member = TeamMember::create(['name' => 'Awa Koné', 'role_title' => 'Data Scientist', 'is_active' => true, 'position' => 1]);
    TeamMember::create(['name' => 'Membre masqué', 'role_title' => 'X', 'is_active' => false, 'position' => 2]);

    $this->get('/equipe')
        ->assertOk()
        ->assertSee('Awa Koné')
        ->assertSee('Data Scientist')
        ->assertDontSee('Membre masqué');
});

// ---------- Admin ----------

it('autorise un éditeur à créer une réalisation et refuse le support', function (): void {
    Volt::actingAs(prjUser(Role::Editor->value))->test('pages.admin.projects')
        ->call('create')
        ->set('title', 'Nouvelle étude de cas')
        ->set('category', 'Automatisation')
        ->set('status', Publication::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(Project::firstWhere('title', 'Nouvelle étude de cas'))->not->toBeNull();

    $this->actingAs(prjUser(Role::Support->value))->get('/admin/realisations')->assertForbidden();
});
