<?php

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'orders' => Order::forUser(auth()->user())->withCount('items')->latest()->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Mes achats</h1>
        </div>
        <x-tk.button href="{{ url('/boutique') }}" variant="secondary" icon="store">Boutique</x-tk.button>
    </div>

    <div class="mt-8">
        @forelse ($orders as $order)
            <a href="{{ route('app.orders.show', $order->number) }}" wire:navigate wire:key="o-{{ $order->id }}"
               class="flex items-center justify-between gap-4 border-b border-line py-4 transition-colors hover:bg-tk-50">
                <div>
                    <p class="font-mono text-sm font-bold text-content">{{ $order->number }}</p>
                    <p class="text-xs text-muted">{{ $order->created_at->format('d/m/Y') }} · {{ $order->items_count }} article(s) · {{ $order->totalLabel() }}</p>
                </div>
                <x-tk.badge :tone="$order->status->tone()" dot>{{ $order->status->label() }}</x-tk.badge>
            </a>
        @empty
            <x-tk.empty-state icon="shopping-bag" title="Aucune commande">
                Vos achats en boutique apparaîtront ici.
                <x-slot:action><x-tk.button href="{{ url('/boutique') }}" variant="primary" icon="store">Voir la boutique</x-tk.button></x-slot:action>
            </x-tk.empty-state>
        @endforelse
    </div>
</div>
