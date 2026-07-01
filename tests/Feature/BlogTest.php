<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\Role;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function blogUser(string $role): User
{
    $u = User::factory()->create();
    $u->assignRole($role);

    return $u;
}

it('affiche le blog avec les articles publiés', function (): void {
    $this->get('/blog')
        ->assertOk()
        ->assertSee('Idées, conseils')
        ->assertSee('IA appliquée : mythes et réalités', false);
});

it('affiche un article publié et 404 un brouillon ou un article futur', function (): void {
    $published = Post::factory()->create(['title' => 'Article visible', 'status' => Publication::Published, 'published_at' => now()->subDay()]);
    $draft = Post::factory()->draft()->create(['title' => 'Article brouillon']);
    $future = Post::factory()->scheduled()->create(['title' => 'Article futur']);

    $this->get(route('blog.show', $published->slug))->assertOk()->assertSee('Article visible')->assertSee('BlogPosting', false);
    $this->get(route('blog.show', $draft->slug))->assertNotFound();
    $this->get(route('blog.show', $future->slug))->assertNotFound();
});

it('filtre les articles par catégorie', function (): void {
    $cat = PostCategory::create(['name' => 'Cat Blog Test', 'slug' => 'cat-blog-test', 'is_published' => true]);
    Post::factory()->create(['title' => 'Post dans catégorie', 'post_category_id' => $cat->id, 'status' => Publication::Published, 'published_at' => now()->subDay()]);
    Post::factory()->create(['title' => 'Post hors catégorie', 'status' => Publication::Published, 'published_at' => now()->subDay()]);

    Volt::test('pages.public.blog-index')
        ->call('setCategory', 'cat-blog-test')
        ->assertSee('Post dans catégorie')
        ->assertDontSee('Post hors catégorie');
});

it('autorise un éditeur à créer un article et refuse le support', function (): void {
    Volt::actingAs(blogUser(Role::Editor->value))->test('pages.admin.blog')
        ->call('create')
        ->set('title', 'Mon nouvel article de blog')
        ->set('status', Publication::Published->value)
        ->set('published_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Post::firstWhere('title', 'Mon nouvel article de blog'))->not->toBeNull();

    $this->actingAs(blogUser(Role::Support->value))->get('/admin/blog')->assertForbidden();
});
