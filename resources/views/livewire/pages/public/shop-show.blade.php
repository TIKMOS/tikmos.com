<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);
        $this->product = $product->load('category', 'media');
    }

    public function addToCart(?string $size = null): void
    {
        abort_if($this->product->isOutOfStock(), 422);

        $size = $size !== null ? trim($size) : null;

        // Produit à pointures : une taille valide est obligatoire.
        if ($this->product->hasSizes()) {
            if ($size === null || $size === '' || ! in_array($size, $this->product->sizeList(), true)) {
                $this->dispatch('tk-toast', type: 'error', message: 'Veuillez choisir une taille.');

                return;
            }
        } else {
            $size = null;
        }

        app(\App\Services\CartService::class)->add($this->product->id, 1, $size);
        $this->dispatch('cart-updated');
        $this->dispatch('tk-toast', type: 'success', message: 'Ajouté au panier.'.($size ? ' (Taille '.$size.')' : ''));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'gallery' => $this->product->galleryUrls(),
            'similar' => Product::active()
                ->where('id', '!=', $this->product->id)
                ->when($this->product->category_id, fn ($q) => $q->where('category_id', $this->product->category_id))
                ->take(3)->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo :title="$product->name" :description="\Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150) ?: ('Équipements & accessoires de luxe — '.config('app.name'))" />
        @php
            $productJsonLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'image' => $product->mainImageUrl(),
                'description' => \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 300),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product->price,
                    'priceCurrency' => 'XOF',
                    'availability' => $product->isOutOfStock() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        <script type="application/ld+json">{!! $productJsonLd !!}</script>
    @endpush

    <x-tk.section>
        <a href="{{ route('shop') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Boutique</a>

        <div class="mt-6 grid gap-10 lg:grid-cols-2">
            {{-- Galerie --}}
            <div x-data="{ current: @js($product->mainImageUrl()) }">
                <div class="aspect-square overflow-hidden rounded-card border border-line bg-canvas">
                    <template x-if="current"><img :src="current" alt="{{ $product->name }}" class="h-full w-full object-cover"></template>
                    <template x-if="!current"><div class="grid h-full place-items-center"><x-lucide-package class="h-12 w-12 text-tk-300" /></div></template>
                </div>
                @if (count($gallery) > 1)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($gallery as $url)
                            <button type="button" @click="current = @js($url)" class="h-16 w-16 overflow-hidden rounded-tile border-2 transition-colors" :class="current === @js($url) ? 'border-ink' : 'border-line'">
                                <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Infos --}}
            <div x-data="{ size: null }">
                @if ($product->brand)<p class="font-mono text-xs uppercase tracking-[0.18em] text-gold-600">{{ $product->brand }}</p>@endif
                @if ($product->category)<x-tk.kicker tone="muted" class="mt-1">{{ $product->category->name }}</x-tk.kicker>@endif
                <h1 class="mt-3 font-display text-3xl font-black tracking-[-0.02em] text-content md:text-4xl">{{ $product->name }}</h1>
                <div class="mt-4 flex items-baseline gap-3">
                    <span class="font-display text-3xl font-bold text-content">{{ $product->priceLabel() }}</span>
                    @if ($product->compareAtLabel())<span class="text-lg text-tk-400 line-through">{{ $product->compareAtLabel() }}</span>@endif
                </div>

                @if ($product->isOutOfStock())
                    <div class="mt-5"><x-tk.badge tone="danger" dot>Épuisé</x-tk.badge></div>
                @endif

                {{-- Sélecteur de tailles --}}
                @if ($product->hasSizes())
                    <div class="mt-6">
                        <p class="text-sm font-medium text-content">Taille</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($product->sizeList() as $s)
                                <button type="button" @click="size = @js($s)"
                                        class="grid h-11 min-w-[44px] place-items-center rounded-tile border px-3 text-sm font-medium transition-colors"
                                        :class="size === @js($s) ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink'">{{ $s }}</button>
                            @endforeach
                        </div>
                        <p x-show="!size" class="mt-2 text-xs text-muted">Sélectionnez votre pointure.</p>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($product->isOutOfStock())
                        <x-tk.button variant="secondary" size="lg" icon="shopping-cart" class="pointer-events-none opacity-50">Indisponible</x-tk.button>
                    @else
                        <x-tk.button x-on:click="$wire.addToCart(size)" variant="primary" size="lg" icon="shopping-cart">Ajouter au panier</x-tk.button>
                    @endif
                    <x-tk.whatsapp-button :floating="false" :message="'Bonjour '.config('app.name').', je suis intéressé(e) par : '.$product->name" />
                </div>

                {{-- Réassurance --}}
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach (['truck' => 'Paiement à la livraison', 'shield-check' => 'Produits vérifiés', 'route' => 'Suivi de commande'] as $rIcon => $rLabel)
                        <div class="flex items-center gap-2 rounded-tile bg-canvas px-3 py-2.5 text-xs text-content"><x-dynamic-component :component="'lucide-'.$rIcon" class="h-4 w-4" /> {{ $rLabel }}</div>
                    @endforeach
                </div>

                @if ($product->description)
                    <div class="prose-sz mt-8 max-w-none border-t border-line pt-6 text-content/80">{!! $product->description !!}</div>
                @endif
            </div>
        </div>

        {{-- Produits similaires --}}
        @if ($similar->isNotEmpty())
            <div class="mt-16">
                <h2 class="font-display text-2xl font-bold text-content">Vous aimerez aussi</h2>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $p)
                        <a href="{{ route('shop.show', $p->slug) }}" wire:navigate class="group flex flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop">
                            <div class="aspect-square overflow-hidden bg-canvas">
                                @if ($p->mainImageUrl())<img src="{{ $p->mainImageUrl() }}" alt="{{ $p->name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">@else<div class="grid h-full place-items-center"><x-lucide-package class="h-8 w-8 text-tk-300" /></div>@endif
                            </div>
                            <div class="p-4">
                                <p class="font-medium text-content">{{ $p->name }}</p>
                                <p class="mt-1 font-display font-bold text-content">{{ $p->priceLabel() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-tk.section>
</div>
