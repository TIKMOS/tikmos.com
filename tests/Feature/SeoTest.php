<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('génère un sitemap couvrant le contenu Tikmos', function (): void {
    $service = Service::factory()->create(['status' => Publication::Published]);
    $post = Post::factory()->create(['status' => Publication::Published, 'published_at' => now()->subDay()]);

    $res = $this->get('/sitemap.xml');

    $res->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset', false)
        ->assertSee(route('services'), false)
        ->assertSee(route('blog'), false)
        ->assertSee(route('services.show', $service->slug), false)
        ->assertSee(route('blog.show', $post->slug), false);
});

it('expose des méta et un JSON-LD Person sur l’accueil', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('"@type":"Person"', false)
        ->assertSee('Moïse TIKO', false);
});

it('fournit un robots.txt statique protégeant les espaces privés', function (): void {
    // Fichier statique servi par le serveur web (hors kernel Laravel) — on vérifie son contenu.
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /admin')
        ->and($robots)->toContain('Disallow: /app')
        ->and($robots)->toContain('Sitemap:');
});
