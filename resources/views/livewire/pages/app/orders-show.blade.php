<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $this->order = $order->load('items');
    }

    public function confirmReceived(): void
    {
        abort_unless($this->order->user_id === auth()->id(), 403);
        abort_unless($this->order->status === OrderStatus::Delivered, 422);

        $this->order->update(['received_confirmed_at' => now()]);
        $this->dispatch('tk-toast', type: 'success', message: 'Merci ! Réception confirmée.');
    }
}; ?>

<div>
    <a href="{{ route('app.orders') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Mes achats</a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">{{ $order->number }}</h1>
            <p class="mt-1 text-muted">{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->payment_method->label() }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <x-tk.badge :tone="$order->status->tone()" dot>{{ $order->status->label() }}</x-tk.badge>
            <x-tk.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-tk.badge>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Suivi --}}
            <x-tk.card>
                <x-tk.kicker>Suivi</x-tk.kicker>
                <div class="mt-5"><x-tk.stepper :steps="$order->timelineSteps()" /></div>

                @if ($order->status === \App\Enums\OrderStatus::Delivered && ! $order->received_confirmed_at)
                    <div class="mt-5 rounded-tile bg-mint/40 p-4">
                        <p class="text-sm font-medium text-content">Avez-vous bien reçu votre commande ?</p>
                        <x-tk.button wire:click="confirmReceived" variant="primary" size="sm" icon="check" class="mt-3">Confirmer la réception</x-tk.button>
                    </div>
                @elseif ($order->received_confirmed_at)
                    <p class="mt-4 inline-flex items-center gap-1.5 text-sm text-success-ink"><x-lucide-circle-check class="h-4 w-4" /> Réception confirmée le {{ $order->received_confirmed_at->format('d/m/Y') }}</p>
                @endif
            </x-tk.card>

            {{-- Articles --}}
            <x-tk.card>
                <x-tk.kicker>Articles</x-tk.kicker>
                <div class="mt-4 divide-y divide-line">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm" wire:key="oi-{{ $item->id }}">
                            <span class="text-content">{{ $item->quantity }} × {{ $item->name }}@if ($item->size) <span class="text-muted">· Taille {{ $item->size }}</span>@endif</span>
                            <span class="font-medium text-content">{{ $item->lineTotalLabel() }}</span>
                        </div>
                    @endforeach
                </div>
                <dl class="mt-3 space-y-1.5 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd class="text-content">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd class="text-content">{{ $order->delivery_fee > 0 ? number_format($order->delivery_fee, 0, ',', ' ').' FCFA' : 'À convenir' }}</dd></div>
                    <div class="flex justify-between text-base"><dt class="font-bold text-content">Total</dt><dd class="font-display font-bold text-content">{{ $order->totalLabel() }}</dd></div>
                </dl>
            </x-tk.card>
        </div>

        <div>
            <x-tk.card>
                <x-tk.kicker>Livraison</x-tk.kicker>
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-muted">Destinataire</dt><dd class="font-medium text-content">{{ $order->contact_name }}</dd></div>
                    <div><dt class="text-muted">Téléphone</dt><dd class="font-medium text-content">{{ $order->contact_phone }}</dd></div>
                    <div><dt class="text-muted">Adresse</dt><dd class="text-content">{{ $order->delivery_address }}{{ $order->delivery_city ? ', '.$order->delivery_city : '' }}</dd></div>
                </dl>
            </x-tk.card>
        </div>
    </div>
</div>
