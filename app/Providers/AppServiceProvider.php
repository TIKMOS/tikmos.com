<?php

namespace App\Providers;

use App\Models\FaqItem;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Review;
use App\Models\TeamMember;
use App\Settings\FeatureSettings;
use App\Settings\GeneralSettings;
use App\Settings\HomepageSettings;
use App\Settings\SeoSettings;
use App\Settings\ShopSettings;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // HTTPS forcé pour la génération d'URL en production (PWA, liens de notif).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Réglages généraux partagés avec le layout public (bandeau, nav, footer, contacts).
        View::composer('components.layouts.public', function (\Illuminate\View\View $view): void {
            $general = app(GeneralSettings::class);
            $view->with('general', $general)
                ->with('seo', app(SeoSettings::class))
                ->with('features', app(FeatureSettings::class))
                ->with('waDigits', preg_replace('/\D/', '', $general->whatsapp_number))
                ->with('footerPartners', Partner::active()->get())
                ->with('shopEnabled', app(ShopSettings::class)->enabled);
        });

        // Données dynamiques de la page d'accueil (contenu éditable + social proof).
        View::composer('home', function (\Illuminate\View\View $view): void {
            $view->with([
                'homepage' => app(HomepageSettings::class),
                'faqItems' => FaqItem::published()->get(),
                'featuredReviews' => Review::featured()->take(8)->get(),
                'teamMembers' => TeamMember::active()->take(4)->get(),
                'featuredProjects' => Project::published()->take(3)->get(),
                'statCount' => app(GeneralSettings::class)->delivered_base,
            ]);
        });
    }
}
