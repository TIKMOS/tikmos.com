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
        <x-tk.seo title="Boutique" description="Compléments et produits sélectionnés par SEZY, livrés près de chez vous." />
    @endpush

    <x-tk.section>
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Boutique</x-tk.kicker>
            <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Sélection SEZY.</h1>
            <p class="mt-4 max-w-md text-lg text-muted">Des produits choisis avec soin, livrés près de chez vous. Paiement à la livraison.</p>
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
                <a href="{{ route('shop.show', $product->slug) }}" wire:navigate wire:key="prod-{{ $product->id }}"
                   class="group flex flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop">
                    <div class="relative aspect-square overflow-hidden bg-canvas">
                        @if ($product->mainImageUrl())
                            <img src="{{ $product->mainImageUrl() }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="grid h-full place-items-center"><x-lucide-package class="h-10 w-10 text-tk-300" /></div>
                        @endif
                        @if ($product->compareAtLabel())<span class="absolute left-3 top-3 rounded-pill bg-ink px-3 py-1 text-xs font-semibold text-paper">Promo</span>@endif
                        @if ($product->isOutOfStock())<span class="absolute right-3 top-3 rounded-pill bg-surface/90 px-3 py-1 text-xs font-semibold text-content">Rupture</span>@endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        @if ($product->category)<p class="text-xs uppercase tracking-wider text-tk-400">{{ $product->category->name }}</p>@endif
                        <h2 class="mt-1 font-display text-lg font-bold text-content">{{ $product->name }}</h2>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-display text-lg font-bold text-content">{{ $product->priceLabel() }}</span>
                            @if ($product->compareAtLabel())<span class="text-sm text-tk-400 line-through">{{ $product->compareAtLabel() }}</span>@endif
                        </div>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">Voir le produit <x-lucide-arrow-right class="h-4 w-4 transition-all" /></span>
                    </div>
                </a>
            @empty
                <div class="sm:col-span-2 lg:col-span-3"><x-tk.empty-state icon="shopping-bag" title="Aucun produit">La sélection arrive bientôt.</x-tk.empty-state></div>
            @endforelse
        </div>

        @if ($products->hasPages())<div class="mt-8">{{ $products->links() }}</div>@endif
    </x-tk.section>
</div>
