<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| Espace livreur (transporteur) — préfixe /livreur, nom livreur.*.
| Cloisonné : le livreur ne voit que SES livraisons (permission deliveries.own).
*/

Route::middleware(['auth', 'approved', 'role:'.Role::Livreur->value])->group(function (): void {
    Volt::route('/', 'pages.livreur.deliveries')->name('deliveries');
});
