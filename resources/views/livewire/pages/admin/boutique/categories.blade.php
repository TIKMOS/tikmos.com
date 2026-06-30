<?php

use App\Models\ProductCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function save(): void
    {
        $data = $this->validate(['name' => ['required', 'string', 'max:80']]);

        if ($this->editingId) {
            ProductCategory::findOrFail($this->editingId)->update(['name' => $data['name']]);
        } else {
            ProductCategory::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'position' => (int) ProductCategory::max('position') + 1,
            ]);
        }
        $this->reset(['name', 'editingId']);
        $this->dispatch('sz-toast', type: 'success', message: 'Catégorie enregistrée.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (ProductCategory::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function edit(ProductCategory $category): void
    {
        $this->editingId = $category->id;
        $this->name = $category->name;
    }

    public function toggle(ProductCategory $category): void
    {
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(ProductCategory $category): void
    {
        $category->delete();
        $this->dispatch('sz-toast', type: 'success', message: 'Catégorie supprimée.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['categories' => ProductCategory::withCount('products')->orderBy('position')->get()];
    }
}; ?>

<div>
    <a href="{{ route('admin.boutique') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Produits</a>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Catégories</h1>

    <form wire:submit="save" class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-end">
        <x-tk.field label="{{ $editingId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}" :error="$errors->first('name')" class="flex-1">
            <x-tk.input wire:model="name" placeholder="Compléments, Vêtements…" />
        </x-tk.field>
        <x-tk.button type="submit" variant="primary">{{ $editingId ? 'Mettre à jour' : 'Ajouter' }}</x-tk.button>
        @if ($editingId)<x-tk.button type="button" variant="ghost" wire:click="$set('editingId', null)">Annuler</x-tk.button>@endif
    </form>

    <div class="mt-8 space-y-2">
        @forelse ($categories as $cat)
            <div wire:key="cat-{{ $cat->id }}" class="flex items-center gap-3 rounded-card border border-line bg-surface p-3 pr-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-content"><x-lucide-tag class="h-4 w-4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-content">{{ $cat->name }}</p>
                    <p class="text-xs text-muted">{{ $cat->products_count }} produit(s)</p>
                </div>
                <button wire:click="toggle({{ $cat->id }})"><x-tk.badge :tone="$cat->is_active ? 'success' : 'neutral'" dot>{{ $cat->is_active ? 'Active' : 'Masquée' }}</x-tk.badge></button>
                <button wire:click="edit({{ $cat->id }})" class="grid h-9 w-9 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                <button wire:click="delete({{ $cat->id }})" wire:confirm="Supprimer ? Les produits liés perdent leur catégorie." class="grid h-9 w-9 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
            </div>
        @empty
            <x-tk.empty-state icon="tags" title="Aucune catégorie">Créez vos catégories de produits.</x-tk.empty-state>
        @endforelse
    </div>
</div>
