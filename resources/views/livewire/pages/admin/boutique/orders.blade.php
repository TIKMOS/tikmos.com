<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = '';

    public ?int $selectedId = null;

    protected function authorizeUpdate(): void
    {
        abort_unless(auth()->user()->can('shop.orders.update'), 403);
    }

    public function open(int $id): void
    {
        $this->selectedId = $id;
    }

    public function close(): void
    {
        $this->selectedId = null;
    }

    public function selected(): ?Order
    {
        return $this->selectedId ? Order::with('items', 'user', 'delivery.courier', 'delivery.livreur')->find($this->selectedId) : null;
    }

    public function setStatus(string $status): void
    {
        $this->authorizeUpdate();
        $order = $this->selected();
        $target = OrderStatus::from($status);

        if (! $order || ! $order->status->canTransitionTo($target)) {
            $this->dispatch('sz-toast', type: 'error', message: 'Transition non autorisée.');

            return;
        }

        $stamps = match ($target) {
            OrderStatus::Confirmed => ['confirmed_at' => now()],
            OrderStatus::Shipped => ['shipped_at' => now()],
            OrderStatus::Delivered => ['delivered_at' => now()],
            default => [],
        };
        $order->update(['status' => $target, ...$stamps]);
        $order->user?->notify(new \App\Notifications\OrderStatusChanged($order));
        $this->dispatch('sz-toast', type: 'success', message: 'Statut mis à jour : '.$target->label());
    }

    public function markPaid(): void
    {
        $this->authorizeUpdate();
        $this->selected()?->update(['payment_status' => OrderPaymentStatus::Paid]);
        $this->dispatch('sz-toast', type: 'success', message: 'Encaissement enregistré.');
    }

    public function assignCourier(\App\Services\DeliveryService $service, int $courierId, ?int $livreurId = null): void
    {
        $this->authorizeUpdate();
        $order = $this->selected();
        $courier = \App\Models\Courier::find($courierId);
        if (! $order || ! $courier) {
            return;
        }
        $delivery = $service->ensureForOrder($order, $courier);
        if ($livreurId) {
            $service->assignLivreur($delivery, \App\Models\User::findOrFail($livreurId));
        }
        $this->dispatch('sz-toast', type: 'success', message: 'Transporteur affecté.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Order::with('user')->withCount('items')->latest();
        if ($this->filter !== '') {
            $query->where('status', $this->filter);
        }

        return [
            'orders' => $query->paginate(15),
            'statuses' => OrderStatus::cases(),
            'couriers' => \App\Models\Courier::active()->with('livreurs')->get(),
        ];
    }
}; ?>

<div>
    <div>
        <x-tk.kicker>Boutique</x-tk.kicker>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Commandes</h1>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button wire:click="$set('filter', '')" @class(['rounded-pill border px-4 py-1.5 text-sm font-medium', 'border-ink bg-ink text-paper' => $filter === '', 'border-line bg-surface text-content hover:border-ink' => $filter !== ''])>Toutes</button>
        @foreach ($statuses as $s)
            <button wire:click="$set('filter', '{{ $s->value }}')" @class(['rounded-pill border px-4 py-1.5 text-sm font-medium', 'border-ink bg-ink text-paper' => $filter === $s->value, 'border-line bg-surface text-content hover:border-ink' => $filter !== $s->value])>{{ $s->label() }}</button>
        @endforeach
    </div>

    <div class="mt-6">
        @forelse ($orders as $order)
            <button wire:key="ord-{{ $order->id }}" wire:click="open({{ $order->id }})" class="flex w-full items-center justify-between gap-4 border-b border-line py-3.5 text-left hover:bg-tk-50">
                <div>
                    <p class="font-mono text-sm font-bold text-content">{{ $order->number }}</p>
                    <p class="text-xs text-muted">{{ $order->user?->name }} · {{ $order->items_count }} art. · {{ $order->totalLabel() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-tk.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-tk.badge>
                    <x-tk.badge :tone="$order->status->tone()" dot>{{ $order->status->label() }}</x-tk.badge>
                </div>
            </button>
        @empty
            <x-tk.empty-state icon="shopping-bag" title="Aucune commande">Les commandes apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($orders->hasPages())<div class="mt-6">{{ $orders->links() }}</div>@endif

    {{-- Détail --}}
    @php($order = $this->selected())
    @if ($order)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/40" @keydown.escape.window="$wire.close()">
            <div @click.outside="$wire.close()" class="h-full w-full max-w-md overflow-y-auto bg-surface p-6 shadow-lift">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-mono font-bold text-content">{{ $order->number }}</p>
                        <p class="text-sm text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <button wire:click="close" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-tk.badge :tone="$order->status->tone()" dot>{{ $order->status->label() }}</x-tk.badge>
                    <x-tk.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-tk.badge>
                </div>

                <div class="mt-5 rounded-card bg-canvas p-4 text-sm">
                    <p class="font-medium text-content">{{ $order->contact_name }}</p>
                    <p class="text-muted">{{ $order->contact_phone }}</p>
                    <p class="mt-1 text-content">{{ $order->delivery_address }}{{ $order->delivery_city ? ', '.$order->delivery_city : '' }}</p>
                    @if ($order->notes)<p class="mt-2 text-xs text-muted">Note : {{ $order->notes }}</p>@endif
                </div>

                <div class="mt-4 divide-y divide-line">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-3 py-2 text-sm" wire:key="aoi-{{ $item->id }}">
                            <span class="text-content">{{ $item->quantity }} × {{ $item->name }}@if ($item->size) <span class="text-muted">· Taille {{ $item->size }}</span>@endif</span>
                            <span class="font-medium text-content">{{ $item->lineTotalLabel() }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between py-2 text-base"><span class="font-bold text-content">Total</span><span class="font-display font-bold text-content">{{ $order->totalLabel() }}</span></div>
                </div>

                @can('shop.orders.update')
                    <div class="mt-6 space-y-3 border-t border-line pt-4">
                        @if (! empty($order->status->nextStates()))
                            <div>
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-tk-400">Faire avancer</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($order->status->nextStates() as $next)
                                        <x-tk.button wire:click="setStatus('{{ $next->value }}')" variant="{{ $next === \App\Enums\OrderStatus::Cancelled ? 'secondary' : 'primary' }}" size="sm">{{ $next->label() }}</x-tk.button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($order->payment_status !== \App\Enums\OrderPaymentStatus::Paid)
                            <x-tk.button wire:click="markPaid" variant="secondary" size="sm" icon="wallet">Marquer payée (COD encaissé)</x-tk.button>
                        @endif

                        {{-- Livraison --}}
                        <div class="w-full" x-data="{ courier: {{ $order->courier_id ?? 'null' }}, livreur: '' }">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-tk-400">Livraison</p>
                            @if ($order->delivery)
                                <p class="mb-2 text-sm text-content">
                                    <x-tk.badge :tone="$order->delivery->status->tone()" dot>{{ $order->delivery->status->label() }}</x-tk.badge>
                                    @if ($order->delivery->courier)<span class="ml-1 text-muted">· {{ $order->delivery->courier->name }}</span>@endif
                                    @if ($order->delivery->livreur)<span class="text-muted">({{ $order->delivery->livreur->name }})</span>@endif
                                </p>
                            @endif
                            @if ($couriers->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-2">
                                    <select x-model="courier" class="min-h-[40px] rounded-input border border-tk-300 bg-surface px-3 text-sm focus:border-ink focus:outline-none">
                                        <option value="">— Transporteur —</option>
                                        @foreach ($couriers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                    </select>
                                    <x-tk.button x-show="courier" x-on:click="$wire.assignCourier(courier, livreur || null)" variant="primary" size="sm" icon="truck">Affecter</x-tk.button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>
