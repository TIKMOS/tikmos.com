<?php

declare(strict_types=1);

use App\Models\FaqItem;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche la page témoignages avec les avis approuvés', function (): void {
    Review::create(['author_name' => 'Client Visible', 'author_city' => 'Cotonou', 'rating' => 5, 'body' => 'Super accompagnement Tikmos.', 'source' => 'manual', 'status' => 'approved', 'is_featured' => false, 'published_at' => now()]);
    Review::create(['author_name' => 'Client Cache', 'rating' => 3, 'body' => 'En attente.', 'source' => 'manual', 'status' => 'pending']);

    $this->get('/temoignages')
        ->assertOk()
        ->assertSee('Ils nous font confiance')
        ->assertSee('Client Visible')
        ->assertDontSee('Client Cache');
});

it('affiche la page FAQ avec les questions publiées et le JSON-LD', function (): void {
    FaqItem::create(['question' => 'Question publiée Tikmos ?', 'answer' => '<p>Oui.</p>', 'category' => 'Général', 'position' => 50, 'is_published' => true]);
    FaqItem::create(['question' => 'Question masquée ?', 'answer' => '<p>Non.</p>', 'position' => 51, 'is_published' => false]);

    $this->get('/faq')
        ->assertOk()
        ->assertSee('Questions fréquentes')
        ->assertSee('Question publiée Tikmos ?')
        ->assertDontSee('Question masquée ?')
        ->assertSee('FAQPage', false); // données structurées
});

it('rend le composant accordéon', function (): void {
    $items = collect([
        (object) ['question' => 'Q accordéon test', 'answer' => '<p>Réponse.</p>'],
    ]);

    $this->blade('<x-tk.accordion :items="$items" />', ['items' => $items])
        ->assertSee('Q accordéon test');
});
