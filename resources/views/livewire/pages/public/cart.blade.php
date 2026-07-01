<?php

use App\Services\CartService;
use App\Settings\GeneralSettings;
use App\Settings\ShopSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    public function updateQty(CartService $cart, string $key, int $qty): void
    {
        $cart->update($key, $qty);
        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cart, string $key): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
        $this->dispatch('sz-toast', type: 'success', message: 'Article retiré.');
    }

    public function checkout(): void
    {
        $this->redirect(route('checkout'), navigate: true);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $cart = app(CartService::class);
        $items = $cart->items();
        $subtotal = $cart->subtotal();
        $deliveryFee = app(ShopSettings::class)->default_delivery_fee;

        // Message WhatsApp pré-rempli avec le détail de la commande.
        $whatsappHref = null;
        if ($items->isNotEmpty()) {
            $lines = $items->map(fn ($i) => '- '.$i['product']->name.($i['size'] ? ' (Taille '.$i['size'].')' : '').' x'.$i['qty'])->implode("\n");
            $message = 'Bonjour '.config('app.name').", je souhaite commander :\n".$lines."\nTotal estimé : ".number_format($subtotal + $deliveryFee, 0, ',', ' ').' FCFA';
            $number = preg_replace('/\D/', '', app(GeneralSettings::class)->whatsapp_number);
            $whatsappHref = 'https://wa.me/'.$number.'?text='.rawurlencode($message);
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'whatsappHref' => $whatsappHref,
        ];
    }
}; ?>

<div>
    @push('meta')<x-tk.seo title="Mon panier" />@endpush

    <x-tk.section>
        <h1 class="font-display text-4xl font-black tracking-[-0.02em] text-content md:text-5xl">Mon panier</h1>

        @if ($items->isEmpty())
            <div class="mt-10"><x-tk.empty-state icon="shopping-cart" title="Votre panier est vide">
                Parcourez la boutique pour ajouter des chaussures.
                <x-slot:action><x-tk.button href="{{ route('shop') }}" variant="primary" icon="store">Voir la boutique</x-tk.button></x-slot:action>
            </x-tk.empty-state></div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-[1.6fr_1fr]">
                {{-- Lignes --}}
                <div class="space-y-3">
                    @foreach ($items as $item)
                        @php($product = $item['product'])
                        <div wire:key="ci-{{ \Illuminate\Support\Str::slug($item['key']) }}" class="flex items-center gap-4 rounded-card border border-line bg-surface p-4">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-tile bg-canvas">
                                @if ($product->mainImageUrl('thumb'))<img src="{{ $product->mainImageUrl('thumb') }}" alt="{{ $product->name }}" class="h-full w-full object-cover">@else<div class="grid h-full place-items-center"><x-lucide-footprints class="h-6 w-6 text-tk-300" /></div>@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('shop.show', $product->slug) }}" wire:navigate class="font-medium text-content hover:underline">{{ $product->name }}</a>
                                @if ($item['size'])<p class="text-sm text-content">Taille : <span class="font-medium">{{ $item['size'] }}</span></p>@endif
                                <p class="text-sm text-muted">{{ $product->priceLabel() }}</p>
                            </div>
                            <div class="flex items-center rounded-btn border border-tk-300">
                                <button wire:click="updateQty('{{ $item['key'] }}', {{ $item['qty'] - 1 }})" class="grid h-9 w-9 place-items-center text-content hover:bg-tk-50" aria-label="Moins"><x-lucide-minus class="h-4 w-4" /></button>
                                <span class="w-8 text-center text-sm font-medium">{{ $item['qty'] }}</span>
                                <button wire:click="updateQty('{{ $item['key'] }}', {{ $item['qty'] + 1 }})" class="grid h-9 w-9 place-items-center text-content hover:bg-tk-50" aria-label="Plus"><x-lucide-plus class="h-4 w-4" /></button>
                            </div>
                            <p class="hidden w-28 text-right font-display font-bold text-content sm:block">{{ number_format($item['line_total'], 0, ',', ' ') }} FCFA</p>
                            <button wire:click="remove('{{ $item['key'] }}')" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger" aria-label="Retirer"><x-lucide-trash-2 class="h-4 w-4" /></button>
                        </div>
                    @endforeach
                </div>

                {{-- Récap --}}
                <div>
                    <div class="rounded-card border border-line bg-surface p-6">
                        <h2 class="font-display text-lg font-bold text-content">Récapitulatif</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd class="font-medium text-content">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</dd></div>
                            <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd class="font-medium text-content">{{ $deliveryFee > 0 ? number_format($deliveryFee, 0, ',', ' ').' FCFA' : 'À convenir' }}</dd></div>
                            <div class="flex justify-between border-t border-line pt-2 text-base"><dt class="font-bold text-content">Total</dt><dd class="font-display font-bold text-content">{{ number_format($subtotal + $deliveryFee, 0, ',', ' ') }} FCFA</dd></div>
                        </dl>
                        <x-tk.button wire:click="checkout" variant="primary" size="lg" icon="arrow-right" :iconRight="true" class="mt-5 w-full">Commander</x-tk.button>
                        @if ($whatsappHref)
                            <x-tk.button href="{{ $whatsappHref }}" target="_blank" variant="whatsapp" size="lg" class="mt-3 w-full">Commander sur WhatsApp</x-tk.button>
                        @endif
                        <p class="mt-3 text-center text-xs text-muted">Paiement à la livraison · Suivi de commande</p>
                    </div>
                    <a href="{{ route('shop') }}" wire:navigate class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-arrow-left class="h-4 w-4" /> Continuer mes achats</a>
                </div>
            </div>
        @endif
    </x-tk.section>
</div>
