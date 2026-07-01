<?php

use App\Settings\FeatureSettings;
use App\Settings\ShopSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public bool $shop = false;

    public bool $blog = false;

    public bool $pwa = false;

    public function mount(FeatureSettings $f, ShopSettings $s): void
    {
        $this->shop = $s->enabled;
        $this->blog = $f->blog;
        $this->pwa = $f->pwa;
    }

    public function save(FeatureSettings $f, ShopSettings $s): void
    {
        $s->enabled = $this->shop;
        $s->save();

        $f->blog = $this->blog;
        $f->pwa = $this->pwa;
        $f->save();

        $this->dispatch('tk-toast', type: 'success', message: 'Fonctionnalités mises à jour.');
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Fonctionnalités</h1>
            <p class="mt-2 max-w-xl text-muted">Activez ou masquez des pans entiers du site sans redéploiement.</p>
        </div>
        <x-tk.button wire:click="save" variant="primary" icon="check">
            <span wire:loading.remove wire:target="save">Enregistrer</span>
            <span wire:loading wire:target="save">Enregistrement…</span>
        </x-tk.button>
    </div>

    <form wire:submit="save" class="mt-8 space-y-4">
        <x-tk.admin-section title="Boutique" description="Catalogue, panier, commandes et checkout." icon="shopping-bag">
            <x-tk.toggle label="Activer la boutique" wire:model="shop" />
            <p class="mt-2 text-sm text-muted">Désactivée, toutes les pages boutique renvoient une 404 et les liens sont masqués. Le paiement se règle dans <a href="{{ route('admin.reglages.payments') }}" wire:navigate class="font-medium text-content underline">Paiements</a>.</p>
        </x-tk.admin-section>

        <x-tk.admin-section title="Blog" description="Articles et actualités." icon="newspaper">
            <x-tk.toggle label="Activer le blog" wire:model="blog" />
            <p class="mt-2 text-sm text-muted">Désactivé, les pages blog renvoient une 404 et le lien disparaît du menu et du pied de page.</p>
        </x-tk.admin-section>

        <x-tk.admin-section title="Application installable (PWA)" description="Service worker, mode hors-ligne, installation sur l'écran d'accueil." icon="smartphone">
            <x-tk.toggle label="Activer la PWA" wire:model="pwa" />
            <p class="mt-2 text-sm text-muted">Les icônes et le manifeste restent servis ; seul le service worker (offline + installation) est conditionné par ce réglage.</p>
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer</x-tk.button>
        </div>
    </form>
</div>
