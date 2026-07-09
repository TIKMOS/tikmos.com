<?php

use App\Enums\Role;
use App\Models\FaqItem;
use App\Models\LegalDocument;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function crudAdmin(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::SuperAdmin->value);

    return $u;
}

/**
 * Régression : les actions edit()/delete() reçoivent l'ID entier depuis la vue
 * (wire:click="edit({{ $m->id }})"). Avec un modèle route-key custom (slug/number/
 * reference), l'implicit binding échouait → ModelNotFound → 404. On vérifie ici le
 * chemin réel (passage par ID), que les anciens tests (qui passaient le modèle) rataient.
 */
it('ouvre le formulaire d\'édition via l\'ID (pas de 404 de binding)', function (string $component, string $model): void {
    $id = $model::query()->firstOrFail()->id;

    Volt::actingAs(crudAdmin())->test($component)
        ->call('edit', $id)
        ->assertHasNoErrors()
        ->assertSet('editingId', $id);
})->with([
    'services' => ['pages.admin.services', Service::class],
    'réalisations' => ['pages.admin.projects', Project::class],
    'blog' => ['pages.admin.blog', Post::class],
    'produits' => ['pages.admin.boutique.products', Product::class],
    'catégories' => ['pages.admin.boutique.categories', ProductCategory::class],
    'équipe' => ['pages.admin.reglages.team', TeamMember::class],
    'faq' => ['pages.admin.reglages.faq', FaqItem::class],
    'partenaires' => ['pages.admin.reglages.partners', Partner::class],
]);

it('édite une page légale via l\'ID', function (): void {
    $id = LegalDocument::query()->firstOrFail()->id;

    Volt::actingAs(crudAdmin())->test('pages.admin.reglages.pages')
        ->call('edit', $id)
        ->assertHasNoErrors()
        ->assertSet('editingId', $id);
});

it('supprime via l\'ID sans erreur de binding (service & produit)', function (): void {
    $admin = crudAdmin();
    $service = Service::query()->firstOrFail();
    $product = Product::query()->firstOrFail();

    Volt::actingAs($admin)->test('pages.admin.services')->call('delete', $service->id)->assertHasNoErrors();
    Volt::actingAs($admin)->test('pages.admin.boutique.products')->call('delete', $product->id)->assertHasNoErrors();

    expect(Service::find($service->id))->toBeNull()
        ->and(Product::find($product->id))->toBeNull();
});

it('modère un avis via l\'ID', function (): void {
    $id = Review::query()->firstOrFail()->id;

    Volt::actingAs(crudAdmin())->test('pages.admin.avis')
        ->call('delete', $id)
        ->assertHasNoErrors();

    expect(Review::find($id))->toBeNull();
});
