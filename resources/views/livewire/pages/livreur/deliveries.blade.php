<?php

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Services\DeliveryService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.livreur')] class extends Component
{
    public function advance(DeliveryService $service, int $deliveryId, string $status): void
    {
        $delivery = $this->ownDelivery($deliveryId);
        $ok = $service->transition($delivery, DeliveryStatus::from($status));
        $this->dispatch('sz-toast', type: $ok ? 'success' : 'error', message: $ok ? 'Statut mis à jour.' : 'Transition non autorisée.');
    }

    public function fail(DeliveryService $service, int $deliveryId): void
    {
        $delivery = $this->ownDelivery($deliveryId);
        $service->transition($delivery, DeliveryStatus::Failed, 'Échec signalé par le livreur');
        $this->dispatch('sz-toast', type: 'success', message: 'Échec enregistré.');
    }

    protected function ownDelivery(int $id): Delivery
    {
        return Delivery::where('livreur_id', auth()->id())->findOrFail($id);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $base = Delivery::forLivreur(auth()->user())->with('order');

        return [
            'active' => (clone $base)->whereNotIn('status', ['delivered', 'failed'])->latest()->get(),
            'done' => (clone $base)->whereIn('status', ['delivered', 'failed'])->latest()->take(20)->get(),
        ];
    }
}; ?>

<div>
    <h1 class="font-display text-3xl font-extrabold text-content">Mes livraisons</h1>
    <p class="mt-1 text-muted">{{ $active->count() }} en cours</p>

    {{-- En cours --}}
    <div class="mt-6 space-y-3">
        @forelse ($active as $delivery)
            <div wire:key="d-{{ $delivery->id }}" class="rounded-card border border-line bg-surface p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $delivery->order->number }}</p>
                        <p class="text-sm text-muted">{{ $delivery->order->totalLabel() }} · {{ $delivery->order->payment_status->label() }}</p>
                    </div>
                    <x-tk.badge :tone="$delivery->status->tone()" dot>{{ $delivery->status->label() }}</x-tk.badge>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    <p class="flex items-start gap-2"><x-lucide-store class="mt-0.5 h-4 w-4 shrink-0 text-tk-400" /> <span class="text-muted">Retrait :</span> {{ $delivery->pickup_location }}</p>
                    <p class="flex items-start gap-2"><x-lucide-map-pin class="mt-0.5 h-4 w-4 shrink-0 text-content" /> <span class="font-medium text-content">{{ $delivery->dropoff_address }}{{ $delivery->dropoff_city ? ', '.$delivery->dropoff_city : '' }}</span></p>
                    <p class="flex items-center gap-2"><x-lucide-user class="h-4 w-4 shrink-0 text-tk-400" /> {{ $delivery->order->contact_name }}</p>
                    <p class="flex items-center gap-2"><x-lucide-phone class="h-4 w-4 shrink-0 text-tk-400" /> <a href="tel:{{ $delivery->order->contact_phone }}" class="text-content underline-offset-2 hover:underline">{{ $delivery->order->contact_phone }}</a></p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                    @foreach ($delivery->status->nextStates() as $next)
                        @if ($next === \App\Enums\DeliveryStatus::Failed)
                            <x-tk.button wire:click="fail({{ $delivery->id }})" wire:confirm="Signaler un échec de livraison ?" variant="secondary" size="sm" icon="x">Échec</x-tk.button>
                        @else
                            <x-tk.button wire:click="advance({{ $delivery->id }}, '{{ $next->value }}')" variant="primary" size="sm" icon="arrow-right" :iconRight="true">{{ $next->label() }}</x-tk.button>
                        @endif
                    @endforeach
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($delivery->dropoff_address.' '.$delivery->dropoff_city) }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-navigation class="h-4 w-4" /> Itinéraire</a>
                </div>
            </div>
        @empty
            <x-tk.empty-state icon="truck" title="Aucune livraison en cours">Les livraisons qui vous sont affectées apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>

    {{-- Historique --}}
    @if ($done->isNotEmpty())
        <h2 class="mt-10 font-display text-lg font-bold text-content">Historique</h2>
        <div class="mt-3 space-y-2">
            @foreach ($done as $delivery)
                <div wire:key="dd-{{ $delivery->id }}" class="flex items-center justify-between gap-3 rounded-card border border-line bg-surface p-4">
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $delivery->order->number }}</p>
                        <p class="text-xs text-muted">{{ $delivery->dropoff_city }}@if ($delivery->commission_amount) · commission {{ number_format($delivery->commission_amount, 0, ',', ' ') }} FCFA @endif</p>
                    </div>
                    <x-tk.badge :tone="$delivery->status->tone()" dot>{{ $delivery->status->label() }}</x-tk.badge>
                </div>
            @endforeach
        </div>
    @endif
</div>
