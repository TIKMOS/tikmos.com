<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| Espace client (authentifié) — préfixe /app, nom app.* (bootstrap/app.php).
*/

Route::middleware(['auth', 'approved'])->group(function (): void {
    Volt::route('/', 'pages.app.dashboard')->name('dashboard');

    // Services & devis (Tikmos)
    Volt::route('devis', 'pages.app.requests-index')->name('requests');
    Volt::route('devis/{request:reference}', 'pages.app.requests-show')->name('requests.show');

    // Domaine Sezy hérité (colis) — retiré avec son métier ultérieurement.
    Volt::route('colis', 'pages.app.colis-index')->name('colis');
    Volt::route('colis/{reference}', 'pages.app.colis-show')->name('colis.show');
    Volt::route('demandes/nouvelle', 'pages.app.demande-create')->name('demandes.create');
    Volt::route('alertes', 'pages.app.alertes')->name('alertes');
    Volt::route('achats', 'pages.app.orders-index')->name('orders');
    Volt::route('achats/{order:number}', 'pages.app.orders-show')->name('orders.show');
    Volt::route('compte', 'pages.app.compte')->name('compte');
});
