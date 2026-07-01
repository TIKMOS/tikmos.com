<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| Back-office équipe — préfixe /admin, nom admin.* (bootstrap/app.php).
| Réservé aux rôles de gestion (RBAC) + permission fine par section.
*/

$staffRoles = implode('|', array_map(fn (Role $r) => $r->value, Role::management()));

Route::middleware(['auth', 'approved', 'role:'.$staffRoles])->group(function (): void {
    Volt::route('/', 'pages.admin.dashboard')->name('dashboard');

    // Services & devis (Tikmos)
    Volt::route('services', 'pages.admin.services')->middleware('permission:services.manage')->name('services');
    Volt::route('realisations', 'pages.admin.projects')->middleware('permission:projects.manage')->name('projects');
    Volt::route('blog', 'pages.admin.blog')->middleware('permission:blog.manage')->name('blog');
    Volt::route('devis', 'pages.admin.service-requests')->middleware('permission:demande.view')->name('service-requests');
    Volt::route('devis/{request:reference}', 'pages.admin.service-request-show')->middleware('permission:demande.view')->name('service-requests.show');

    Volt::route('clients', 'pages.admin.clients')->middleware('permission:client.view')->name('clients');
    Volt::route('messages', 'pages.admin.messages')->middleware('permission:message.view')->name('messages');
    Volt::route('equipe', 'pages.admin.equipe')->middleware('permission:equipe.view')->name('equipe');
    Volt::route('audit', 'pages.admin.audit')->middleware('permission:audit.view')->name('audit');
    Volt::route('avis', 'pages.admin.avis')->middleware('permission:reviews.view')->name('avis');
    Volt::route('boutique', 'pages.admin.boutique.products')->middleware('permission:shop.products.manage')->name('boutique');
    Volt::route('boutique/categories', 'pages.admin.boutique.categories')->middleware('permission:shop.categories.manage')->name('boutique.categories');
    Volt::route('commandes', 'pages.admin.boutique.orders')->middleware('permission:shop.orders.view')->name('boutique.orders');
    Volt::route('reglages', 'pages.admin.reglages')->middleware('permission:settings.manage')->name('reglages');
    Volt::route('reglages/general', 'pages.admin.reglages.general')->middleware('permission:settings.manage')->name('reglages.general');
    Volt::route('reglages/accueil', 'pages.admin.reglages.homepage')->middleware('permission:cms.homepage.manage')->name('reglages.homepage');
    Volt::route('reglages/faq', 'pages.admin.reglages.faq')->middleware('permission:cms.faq.manage')->name('reglages.faq');
    Volt::route('reglages/pages', 'pages.admin.reglages.pages')->middleware('permission:cms.pages.manage')->name('reglages.pages');
    Volt::route('reglages/partenaires', 'pages.admin.reglages.partners')->middleware('permission:partners.manage')->name('reglages.partners');
    Volt::route('reglages/equipe', 'pages.admin.reglages.team')->middleware('permission:team.manage')->name('reglages.team');
    Volt::route('reglages/paiements', 'pages.admin.reglages.payments')->middleware('permission:settings.manage')->name('reglages.payments');
    Volt::route('reglages/medias', 'pages.admin.reglages.media')->middleware('permission:settings.manage')->name('reglages.media');
    Volt::route('reglages/fonctionnalites', 'pages.admin.reglages.features')->middleware('permission:settings.manage')->name('reglages.features');
});
