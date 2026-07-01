@props([
    'product', // App\Models\Product
])

<a href="{{ route('shop.show', $product->slug) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop']) }}>
    <div class="relative aspect-square overflow-hidden bg-canvas">
        @php($cover = $product->getFirstMedia('gallery'))
        @if ($cover)
            <x-tk.image :media="$cover" :alt="$product->name" sizes="(max-width:640px) 100vw, (max-width:1024px) 50vw, 33vw"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" :fallback="false" />
        @else
            <div class="grid h-full place-items-center"><x-lucide-package class="h-10 w-10 text-tk-300" /></div>
        @endif
        @if ($product->compareAtLabel())<span class="absolute left-3 top-3 rounded-pill bg-gold px-3 py-1 text-xs font-semibold text-ink">Promo</span>@endif
        @if ($product->isOutOfStock())<span class="absolute right-3 top-3 rounded-pill bg-surface/90 px-3 py-1 text-xs font-semibold text-content">Épuisé</span>@endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        @if ($product->brand)<p class="font-mono text-xs uppercase tracking-[0.16em] text-gold-600">{{ $product->brand }}</p>@endif
        <h2 class="mt-1 font-display text-lg font-bold text-content">{{ $product->name }}</h2>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="font-display text-lg font-bold text-content">{{ $product->priceLabel() }}</span>
            @if ($product->compareAtLabel())<span class="text-sm text-tk-400 line-through">{{ $product->compareAtLabel() }}</span>@endif
        </div>
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">Voir le produit <x-lucide-arrow-right class="h-4 w-4 transition-all" /></span>
    </div>
</a>
