<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $cat = '';

    #[Url]
    public string $sort = 'featured';

    public function with(): array
    {
        $query = Product::active()->with('category');

        if ($this->cat !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->cat));
        }

        match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'new' => $query->latest(),
            default => $query->orderByDesc('is_featured')->latest(),
        };

        return [
            'products' => $query->paginate(12),
            'categories' => ProductCategory::active()->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Boutique" description="Équipements informatiques et accessoires de luxe, sélectionnés avec exigence. Commande simple, suivie." />
    @endpush

    <x-tk.section>
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Boutique</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Équipements &amp; accessoires de luxe.</h1>
            <p class="mt-4 max-w-md text-lg text-muted">Matériel informatique et accessoires haut de gamme, choisis avec exigence.</p>
        </div>

        {{-- Filtres --}}
        <div class="mt-8 flex flex-wrap items-center gap-2">
            <button wire:click="$set('cat', '')" @class(['rounded-pill border px-4 py-1.5 text-sm font-medium transition-colors', 'border-ink bg-ink text-paper' => $cat === '', 'border-line bg-surface text-content hover:border-ink' => $cat !== ''])>Tout</button>
            @foreach ($categories as $c)
                <button wire:click="$set('cat', '{{ $c->slug }}')" @class(['rounded-pill border px-4 py-1.5 text-sm font-medium transition-colors', 'border-ink bg-ink text-paper' => $cat === $c->slug, 'border-line bg-surface text-content hover:border-ink' => $cat !== $c->slug])>{{ $c->name }}</button>
            @endforeach
            <select wire:model.live="sort" class="ml-auto min-h-[40px] rounded-input border border-tk-300 bg-surface px-3 text-sm text-content focus:border-ink focus:outline-none">
                <option value="featured">En avant</option>
                <option value="new">Nouveautés</option>
                <option value="price_asc">Prix croissant</option>
                <option value="price_desc">Prix décroissant</option>
            </select>
        </div>

        {{-- Grille --}}
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-50">
            @forelse ($products as $product)
                <x-tk.product-card :product="$product" wire:key="prod-{{ $product->id }}" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3"><x-tk.empty-state icon="shopping-bag" title="Aucun produit">La sélection arrive bientôt.</x-tk.empty-state></div>
            @endforelse
        </div>

        @if ($products->hasPages())<div class="mt-8">{{ $products->links() }}</div>@endif
    </x-tk.section>
</div>
