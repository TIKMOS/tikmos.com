<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| Routes d'authentification — login / register / 2FA / reset / logout.
| Chargées avec le middleware « web » par bootstrap/app.php.
*/

Route::middleware('guest')->group(function (): void {
    Volt::route('connexion', 'pages.auth.login')->name('login');
    Volt::route('inscription', 'pages.auth.register')->name('register');
    Volt::route('mot-de-passe-oublie', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('reinitialiser/{token}', 'pages.auth.reset-password')->name('password.reset');
});

// Challenge 2FA — accessible pendant l'état « connexion en attente » (utilisateur non authentifié).
Volt::route('code-securite', 'pages.auth.two-factor-challenge')->name('two-factor.challenge');

Route::middleware('auth')->group(function (): void {
    Route::post('deconnexion', LogoutController::class)->name('logout');
});
