<?php

use App\Enums\Role;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function richAdmin(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::SuperAdmin->value);

    return $u;
}

$dirtyHtml = '<h2>Programme</h2><p>Contenu <strong>enrichi</strong></p><ul><li>Point</li></ul><script>alert(1)</script>';

it('conserve les tableaux, images alignées et retire les vecteurs XSS', function (): void {
    $dirty = '<h2 style="text-align:center">Titre centré</h2>'
        .'<table><tbody><tr><th>Col</th></tr><tr><td>Cellule</td></tr></tbody></table>'
        .'<p style="text-align:right">Droite</p>'
        .'<img src="/storage/editor/x.png" alt="ok" onerror="alert(1)">'
        .'<a href="javascript:alert(1)">piège</a>'
        .'<script>alert(1)</script>';

    $clean = clean($dirty);

    expect($clean)->toContain('<table>')
        ->and($clean)->toContain('<td>Cellule</td>')
        ->and($clean)->toContain('<th>Col</th>')
        ->and($clean)->toContain('text-align:center')
        ->and($clean)->toContain('text-align:right')
        ->and($clean)->toContain('<img')
        ->and($clean)->toContain('src="/storage/editor/x.png"')
        // Vecteurs retirés
        ->and($clean)->not->toContain('<script>')
        ->and($clean)->not->toContain('onerror')
        ->and($clean)->not->toContain('javascript:');
});

/**
 * Les zones de contenu long (éditeur riche) doivent conserver le HTML de mise en
 * forme MAIS être assainies (pas de <script>) avant stockage — puis rendues en {!! !!}.
 */
it('assainit et conserve le HTML riche du corps (services/réalisations/blog)', function (string $component, string $model, string $field) use ($dirtyHtml): void {
    $id = $model::query()->firstOrFail()->id;

    Volt::actingAs(richAdmin())->test($component)
        ->call('edit', $id)
        ->set($field, $dirtyHtml)
        ->call('save')
        ->assertHasNoErrors();

    $body = (string) $model::find($id)->{$field === 'body' ? 'body' : $field};

    expect($body)->toContain('<strong>enrichi</strong>')
        ->and($body)->toContain('<h2>')
        ->and($body)->toContain('<ul>')
        ->and($body)->not->toContain('<script>');
})->with([
    'services' => ['pages.admin.services', Service::class, 'body'],
    'réalisations' => ['pages.admin.projects', Project::class, 'body'],
    'blog' => ['pages.admin.blog', Post::class, 'body'],
]);

it('assainit aussi la description produit (déjà en éditeur riche)', function () use ($dirtyHtml): void {
    $id = Product::query()->firstOrFail()->id;

    Volt::actingAs(richAdmin())->test('pages.admin.boutique.products')
        ->call('edit', $id)
        ->set('description', $dirtyHtml)
        ->call('save')
        ->assertHasNoErrors();

    $desc = (string) Product::find($id)->description;

    expect($desc)->toContain('<strong>enrichi</strong>')
        ->and($desc)->not->toContain('<script>');
});
