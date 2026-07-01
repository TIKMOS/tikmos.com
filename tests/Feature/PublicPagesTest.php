<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche la page d’accueil Tikmos (héros + services + CTA devis)', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Je conçois, je forme, je livre.', false)
        ->assertSee('Demander un devis')
        ->assertSee('clients et projets accompagnés')
        ->assertSee('Plateforme data décisionnelle', false)
        ->assertSee('Une équipe derrière chaque projet.', false);
});

it('affiche la page À propos (storytelling)', function (): void {
    $this->get('/a-propos')
        ->assertOk()
        ->assertSee('L\'expertise qui exécute', false)
        ->assertSee('Mon parcours');
});

it('expose le health check', function (): void {
    $this->get('/up')->assertOk();
});
