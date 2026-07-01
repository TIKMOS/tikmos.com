<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    /** @return array<int, array<string, string>> */
    public function sections(): array
    {
        return [
            ['label' => 'Général', 'desc' => 'Contacts, bandeau, réseaux, adresses.', 'icon' => 'settings-2', 'route' => 'admin.reglages.general', 'permission' => 'settings.manage', 'tint' => 'sky'],
            ['label' => 'Accueil', 'desc' => 'Hero, sections, ordre de la page d\'accueil.', 'icon' => 'layout-template', 'route' => 'admin.reglages.homepage', 'permission' => 'cms.homepage.manage', 'tint' => 'mint'],
            ['label' => 'FAQ', 'desc' => 'Questions fréquentes, catégories, publication.', 'icon' => 'circle-help', 'route' => 'admin.reglages.faq', 'permission' => 'cms.faq.manage', 'tint' => 'lilac'],
            ['label' => 'Pages légales', 'desc' => 'CGU, CGV, confidentialité — éditeur riche.', 'icon' => 'file-text', 'route' => 'admin.reglages.pages', 'permission' => 'cms.pages.manage', 'tint' => 'peach'],
            ['label' => 'Partenaires', 'desc' => 'Logos de la zone partenaires (avant le footer).', 'icon' => 'handshake', 'route' => 'admin.reglages.partners', 'permission' => 'partners.manage', 'tint' => 'mint'],
            ['label' => 'Équipe', 'desc' => 'Membres affichés sur la page Équipe.', 'icon' => 'users', 'route' => 'admin.reglages.team', 'permission' => 'team.manage', 'tint' => 'lilac'],
            ['label' => 'Avis', 'desc' => 'Modération et mise en avant des témoignages.', 'icon' => 'star', 'route' => 'admin.avis', 'permission' => 'reviews.view', 'tint' => 'peach'],
            ['label' => 'Boutique', 'desc' => 'Produits, catégories, commandes.', 'icon' => 'shopping-bag', 'route' => 'admin.boutique', 'permission' => 'shop.products.manage', 'tint' => 'sky'],
            ['label' => 'Paiements', 'desc' => 'Mode (en ligne / à la livraison), KKiaPay, webhook.', 'icon' => 'wallet', 'route' => 'admin.reglages.payments', 'permission' => 'settings.manage', 'tint' => 'mint'],
            ['label' => 'Médias', 'desc' => 'Formats, poids, compression, rendu des images.', 'icon' => 'image', 'route' => 'admin.reglages.media', 'permission' => 'settings.manage', 'tint' => 'lilac'],
            ['label' => 'Fonctionnalités', 'desc' => 'Activer/masquer boutique, blog, PWA.', 'icon' => 'toggle-right', 'route' => 'admin.reglages.features', 'permission' => 'settings.manage', 'tint' => 'peach'],
        ];
    }
}; ?>

<div>
    <div>
        <x-tk.kicker>Back-office</x-tk.kicker>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Réglages</h1>
        <p class="mt-2 max-w-xl text-muted">Configurez le contenu et les paramètres du site sans toucher au code. Chaque section s'active au fil des modules livrés.</p>
    </div>

    @php($tints = ['sky' => 'bg-sky', 'mint' => 'bg-mint', 'lilac' => 'bg-lilac', 'peach' => 'bg-peach'])
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->sections() as $s)
            @can($s['permission'])
                @php($ready = \Illuminate\Support\Facades\Route::has($s['route']))
                @if ($ready)
                    <a href="{{ route($s['route']) }}" wire:navigate
                       class="group relative flex flex-col rounded-card border border-line/60 {{ $tints[$s['tint']] }} p-6 transition-all hover:-translate-y-0.5 hover:shadow-pop">
                        <div class="flex items-center justify-between">
                            <span class="grid h-11 w-11 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$s['icon']" class="h-5 w-5" /></span>
                            <x-lucide-arrow-up-right class="h-5 w-5 text-content transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </div>
                        <h2 class="mt-4 font-display text-lg font-bold text-content">{{ $s['label'] }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $s['desc'] }}</p>
                    </a>
                @else
                    <div class="relative flex flex-col rounded-card border border-line/60 {{ $tints[$s['tint']] }} p-6 opacity-60">
                        <div class="flex items-center justify-between">
                            <span class="grid h-11 w-11 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$s['icon']" class="h-5 w-5" /></span>
                            <x-tk.badge tone="neutral">À venir</x-tk.badge>
                        </div>
                        <h2 class="mt-4 font-display text-lg font-bold text-content">{{ $s['label'] }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $s['desc'] }}</p>
                    </div>
                @endif
            @endcan
        @endforeach
    </div>
</div>
