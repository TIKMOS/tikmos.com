<?php

use App\Models\Flight;
use App\Models\Parcel;
use App\Models\ParcelRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'colisMonth' => Parcel::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'toShip' => Parcel::where('status', 'registered')->count(),
            'unpaid' => Parcel::where('payment_status', 'unpaid')->count(),
            'flightsUpcoming' => Flight::query()->upcoming()->count(),
            'pendingRequests' => ParcelRequest::where('status', 'new')->count(),
            'recentParcels' => Parcel::with('sender')->latest()->take(6)->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Tableau de bord</h1>
        </div>
        @can('colis.create')
            <x-tk.button href="{{ route('admin.colis') }}" variant="primary" icon="plus">Gérer les colis</x-tk.button>
        @endcan
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-tk.stat label="Colis ce mois" :value="$colisMonth" icon="package" tint="sky" />
        <x-tk.stat label="À expédier" :value="$toShip" icon="plane-takeoff" tint="lilac" />
        <x-tk.stat label="En attente paiement" :value="$unpaid" icon="wallet" tint="peach" />
        <x-tk.stat label="Vols à venir" :value="$flightsUpcoming" icon="calendar" tint="mint" />
    </div>

    @if ($pendingRequests > 0)
        <a href="{{ route('admin.demandes') }}" wire:navigate class="group mt-4 flex items-center gap-3 rounded-card border border-warning/20 bg-warning-soft px-5 py-4 text-sm text-warning-ink transition-colors hover:bg-warning-soft/70">
            <x-lucide-inbox class="h-5 w-5" />
            <span class="font-medium">{{ $pendingRequests }} nouvelle(s) demande(s)</span> à traiter.
            <x-lucide-arrow-right class="ml-auto h-4 w-4 transition-transform group-hover:translate-x-0.5" />
        </a>
    @endif

    <div class="mt-8 flex items-center justify-between">
        <x-tk.kicker>Derniers colis</x-tk.kicker>
        @can('colis.view')<a href="{{ route('admin.colis') }}" wire:navigate class="text-sm font-medium text-content hover:underline">Tout voir</a>@endcan
    </div>
    <div class="mt-4">
        @forelse ($recentParcels as $parcel)
            <a href="{{ route('admin.colis.show', $parcel->reference) }}" wire:navigate class="flex items-center justify-between gap-4 border-b border-line py-3.5 hover:bg-tk-50">
                <div>
                    <p class="font-mono text-sm font-bold text-content">{{ $parcel->reference }}</p>
                    <p class="text-xs text-muted">{{ $parcel->sender?->name ?? $parcel->sender_name ?? 'Walk-in' }} · {{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
                </div>
                <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
            </a>
        @empty
            <x-tk.empty-state icon="package-open" title="Aucun colis">Les colis créés apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>
</div>
