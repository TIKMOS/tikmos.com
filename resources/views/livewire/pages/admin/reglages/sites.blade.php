<?php

use App\Models\PreferredSite;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $url = '';

    public bool $is_active = true;

    public $logo = null;

    public ?string $currentLogo = null;

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'url', 'is_active', 'logo', 'currentLogo']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(PreferredSite $site): void
    {
        $this->editingId = $site->id;
        $this->name = $site->name;
        $this->url = (string) $site->url;
        $this->is_active = $site->is_active;
        $this->currentLogo = $site->logoUrl();
        $this->logo = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $site = $this->editingId
            ? PreferredSite::findOrFail($this->editingId)
            : new PreferredSite(['position' => (int) PreferredSite::max('position') + 1]);

        $site->fill(['name' => $data['name'], 'url' => $data['url'] ?: null, 'is_active' => $this->is_active]);
        $site->save();

        if ($this->logo) {
            $site->addMedia($this->logo->getRealPath())
                ->usingFileName(\Illuminate\Support\Str::slug($this->name).'.'.$this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }

        $this->showForm = false;
        $this->dispatch('sz-toast', type: 'success', message: $this->editingId ? 'Site mis à jour.' : 'Site ajouté.');
    }

    public function toggle(PreferredSite $site): void
    {
        $site->update(['is_active' => ! $site->is_active]);
    }

    public function delete(PreferredSite $site): void
    {
        $site->delete();
        $this->dispatch('sz-toast', type: 'success', message: 'Site supprimé.');
    }

    /** @param  array<int, string>  $ids */
    public function reorder(array $ids): void
    {
        foreach ($ids as $position => $id) {
            PreferredSite::whereKey($id)->update(['position' => $position]);
        }
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['sites' => PreferredSite::orderBy('position')->get()];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Sites préférés</h1>
            <p class="mt-1 text-muted">Marques affichées sur la page Achat. Glissez pour réordonner.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Ajouter un site</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($sites->isEmpty())
            <x-tk.empty-state icon="store" title="Aucun site">Ajoutez les sites e-commerce partenaires.</x-tk.empty-state>
        @else
            <x-tk.sortable-list onReorder="reorder">
                @foreach ($sites as $site)
                    <li wire:key="site-{{ $site->id }}" data-id="{{ $site->id }}"
                        class="flex items-center gap-4 rounded-card border border-line bg-surface p-3 pr-4">
                        <x-lucide-grip-vertical class="h-5 w-5 shrink-0 cursor-grab text-tk-300" />
                        <div class="grid h-12 w-20 shrink-0 place-items-center overflow-hidden rounded-tile border border-line bg-canvas">
                            @if ($site->logoUrl())<img src="{{ $site->logoUrl() }}" alt="{{ $site->name }}" class="max-h-full max-w-full object-contain p-1">@else<x-lucide-image class="h-5 w-5 text-tk-300" />@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $site->name }}</p>
                            @if ($site->url)<p class="truncate text-xs text-muted">{{ $site->url }}</p>@endif
                        </div>
                        <button wire:click="toggle({{ $site->id }})" class="shrink-0">
                            <x-tk.badge :tone="$site->is_active ? 'success' : 'neutral'" dot>{{ $site->is_active ? 'Actif' : 'Masqué' }}</x-tk.badge>
                        </button>
                        <button wire:click="edit({{ $site->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $site->id }})" wire:confirm="Supprimer ce site ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </li>
                @endforeach
            </x-tk.sortable-list>
        @endif
    </div>

    {{-- Modal création/édition --}}
    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
        <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier le site' : 'Nouveau site' }}</h2>
                <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
            </div>
            <form wire:submit="save" class="mt-5 space-y-4">
                <x-tk.field label="Nom" :error="$errors->first('name')"><x-tk.input wire:model="name" placeholder="Amazon" /></x-tk.field>
                <x-tk.field label="URL (optionnel)" :error="$errors->first('url')"><x-tk.input wire:model="url" type="url" placeholder="https://amazon.fr" /></x-tk.field>
                <x-tk.media-input model="logo" :preview="$currentLogo" label="Logo" hint="PNG/JPG/WebP — 2 Mo. Fond transparent conseillé." />
                <x-tk.toggle label="Actif" description="Affiché sur la page Achat." wire:model="is_active" />
                <div class="flex gap-3 pt-2">
                    <x-tk.button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">…</span>
                    </x-tk.button>
                    <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                </div>
            </form>
        </div>
    </div>
</div>
