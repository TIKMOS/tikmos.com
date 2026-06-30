<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'home')->name('home');

// Vitrine publique
Volt::route('services', 'pages.public.services-index')->name('services');
Volt::route('services/{service:slug}', 'pages.public.services-show')->name('services.show');
Volt::route('realisations', 'pages.public.projects-index')->name('projects');
Volt::route('realisations/{project:slug}', 'pages.public.projects-show')->name('projects.show');
Route::view('achat', 'public.achat')->name('achat');
Route::view('a-propos', 'public.about')->name('about');
Route::view('equipe', 'public.team')->name('team');

// Boutique (active seulement si ShopSettings::enabled)
Route::middleware('shop.enabled')->group(function (): void {
    Volt::route('boutique', 'pages.public.shop-index')->name('shop');
    Volt::route('boutique/{product:slug}', 'pages.public.shop-show')->name('shop.show');
    Volt::route('panier', 'pages.public.cart')->name('cart');
    Volt::route('commander', 'pages.public.checkout')->middleware(['auth', 'approved'])->name('checkout');
});
Route::view('cgu', 'public.cgu')->name('cgu');
Route::view('confidentialite', 'public.privacy')->name('privacy');
Route::view('cookies', 'public.cookies')->name('cookies');

Volt::route('vols', 'pages.public.flights')->name('flights');
Volt::route('contact', 'pages.public.contact')->name('contact');
Volt::route('suivi', 'pages.public.track')->name('track');
Volt::route('suivi/{reference}', 'pages.public.track')->name('track.show');

// SEO
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

// Galerie du design system (environnement local uniquement) — lot 02.
if (app()->environment('local')) {
    Route::view('/_design', 'design-demo')->name('design.demo');
}
