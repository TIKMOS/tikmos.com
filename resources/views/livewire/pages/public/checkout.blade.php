<?php

use App\Services\CartService;
use App\Services\OrderService;
use App\Settings\ShopSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $contact_name = '';

    #[Validate('required|string|max:30')]
    public string $contact_phone = '';

    #[Validate('required|string|max:500')]
    public string $delivery_address = '';

    #[Validate('nullable|string|max:120')]
    public string $delivery_city = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    // '' = SEZY (livraison interne), sinon l'identifiant du transporteur partenaire.
    public string $courier_id = '';

    public function mount(CartService $cart): void
    {
        if ($cart->isEmpty()) {
            $this->redirect(route('cart'), navigate: true);

            return;
        }
        $user = auth()->user();
        $this->contact_name = $user->name;
        $this->contact_phone = (string) $user->phone;
        $this->delivery_city = (string) $user->city;
    }

    public function place(OrderService $orders, \App\Services\DeliveryService $deliveries): void
    {
        $data = $this->validate();

        // '' = SEZY interne (courier null) ; sinon transporteur actif valide.
        $courierId = $this->courier_id !== '' ? (int) $this->courier_id : null;
        if ($courierId !== null && ! \App\Models\Courier::active()->whereKey($courierId)->exists()) {
            $courierId = null;
        }
        $data['courier_id'] = $courierId;

        $order = $orders->place(auth()->user(), $data);
        $deliveries->ensureForOrder($order);

        $this->dispatch('cart-updated');
        session()->flash('toast', ['type' => 'success', 'message' => 'Commande confirmée ! Numéro : '.$order->number]);
        $this->redirect(route('app.orders.show', $order->number), navigate: true);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $cart = app(CartService::class);

        return [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => app(ShopSettings::class)->default_delivery_fee,
            'couriers' => \App\Models\Courier::active()->get(),
        ];
    }
}; ?>

<div>
    @push('meta')<x-tk.seo title="Commander" />@endpush

    <x-tk.section>
        <a href="{{ route('cart') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Panier</a>
        <h1 class="mt-3 font-display text-4xl font-black tracking-[-0.02em] text-content md:text-5xl">Finaliser la commande</h1>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
            {{-- Formulaire --}}
            <form wire:submit="place" class="space-y-5">
                <x-tk.admin-section title="Livraison" description="Où devons-nous livrer votre commande ?" icon="map-pin">
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-tk.field label="Nom complet" :error="$errors->first('contact_name')"><x-tk.input wire:model="contact_name" icon="user" /></x-tk.field>
                            <x-tk.field label="Téléphone" :error="$errors->first('contact_phone')"><x-tk.phone wire:model="contact_phone" :invalid="$errors->has('contact_phone')" /></x-tk.field>
                        </div>
                        <x-tk.field label="Adresse de livraison" :error="$errors->first('delivery_address')">
                            <textarea wire:model="delivery_address" rows="2" placeholder="Quartier, rue, repère…" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                        </x-tk.field>
                        <x-tk.field label="Ville" :error="$errors->first('delivery_city')"><x-tk.input wire:model="delivery_city" icon="map-pin" /></x-tk.field>
                        <x-tk.field label="Notes (optionnel)" :error="$errors->first('notes')"><x-tk.input wire:model="notes" placeholder="Précisions de livraison…" /></x-tk.field>
                    </div>
                </x-tk.admin-section>

                @if ($couriers->isNotEmpty())
                    <x-tk.admin-section title="Transporteur" description="Choisissez qui livrera votre commande (optionnel)." icon="truck">
                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-tile border p-3 transition-colors {{ $courier_id === '' ? 'border-ink bg-sky/30' : 'border-line hover:border-tk-400' }}">
                                <input type="radio" wire:model.live="courier_id" value="" @checked($courier_id === '') class="text-ink focus:ring-ink/20">
                                <x-lucide-truck class="h-5 w-5 text-content" />
                                <span class="text-sm font-medium text-content">SEZY (livraison interne)</span>
                            </label>
                            @foreach ($couriers as $c)
                                <label class="flex cursor-pointer items-center gap-3 rounded-tile border p-3 transition-colors {{ $courier_id === (string) $c->id ? 'border-ink bg-sky/30' : 'border-line hover:border-tk-400' }}">
                                    <input type="radio" wire:model.live="courier_id" value="{{ $c->id }}" @checked($courier_id === (string) $c->id) class="text-ink focus:ring-ink/20">
                                    @if ($c->logoUrl())<img src="{{ $c->logoUrl() }}" alt="" class="h-6 w-auto">@else<x-lucide-truck class="h-5 w-5 text-tk-400" />@endif
                                    <span class="text-sm font-medium text-content">{{ $c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </x-tk.admin-section>
                @endif

                <x-tk.admin-section title="Paiement" icon="wallet">
                    <label class="flex items-center gap-3 rounded-tile border-2 border-ink bg-sky/40 p-4">
                        <x-lucide-truck class="h-6 w-6 text-content" />
                        <span><span class="block font-medium text-content">Paiement à la livraison</span><span class="block text-sm text-muted">Réglez en espèces ou Mobile Money à la réception.</span></span>
                        <x-lucide-check-circle-2 class="ml-auto h-5 w-5 text-content" />
                    </label>
                </x-tk.admin-section>

                <x-tk.button type="submit" variant="primary" size="lg" icon="check" class="w-full sm:w-auto">
                    <span wire:loading.remove wire:target="place">Confirmer ma commande</span>
                    <span wire:loading wire:target="place">Traitement…</span>
                </x-tk.button>
            </form>

            {{-- Récap --}}
            <div>
                <div class="rounded-card border border-line bg-surface p-6">
                    <h2 class="font-display text-lg font-bold text-content">Votre commande</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            <div class="flex items-center gap-3 text-sm" wire:key="co-{{ $item['product']->id }}">
                                <span class="grid h-6 min-w-6 place-items-center rounded-full bg-tk-100 px-1 text-xs font-bold text-content">{{ $item['qty'] }}</span>
                                <span class="min-w-0 flex-1 truncate text-content">{{ $item['product']->name }}</span>
                                <span class="font-medium text-content">{{ number_format($item['line_total'], 0, ',', ' ') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd class="font-medium text-content">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd class="font-medium text-content">{{ $deliveryFee > 0 ? number_format($deliveryFee, 0, ',', ' ').' FCFA' : 'À convenir' }}</dd></div>
                        <div class="flex justify-between border-t border-line pt-2 text-base"><dt class="font-bold text-content">Total</dt><dd class="font-display font-bold text-content">{{ number_format($subtotal + $deliveryFee, 0, ',', ' ') }} FCFA</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </x-tk.section>
</div>
