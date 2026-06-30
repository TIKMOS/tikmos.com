<?php

use App\Models\Flight;
use App\Models\Parcel;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        $user = auth()->user();

        return [
            'inProgress' => Parcel::forUser($user)->whereNotIn('status', ['delivered', 'returned'])->count(),
            'delivered' => Parcel::forUser($user)->where('status', 'delivered')->count(),
            'recent' => Parcel::forUser($user)->latest()->take(4)->get(),
            'nextFlight' => Flight::query()->public()->upcoming()->first(),
            'unread' => $user->unreadNotifications()->count(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Bonjour {{ auth()->user()->name }}</h1>
        </div>
        <x-tk.button href="{{ route('app.demandes.create') }}" variant="primary" icon="plus">Nouvelle demande</x-tk.button>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-tk.stat label="Colis en cours" :value="$inProgress" icon="package" tint="sky" />
        <x-tk.stat label="Livrés" :value="$delivered" icon="circle-check" tint="mint" />
        <x-tk.stat label="Prochain vol" :value="$nextFlight?->departs_on->format('d/m') ?? '—'" icon="plane-takeoff" tint="lilac" />
        <x-tk.stat label="Alertes" :value="$unread" icon="bell" tint="peach" />
    </div>

    <div class="mt-8 flex items-center justify-between">
        <x-tk.kicker>Mes colis récents</x-tk.kicker>
        <a href="{{ route('app.colis') }}" wire:navigate class="text-sm font-medium text-content hover:underline">Tout voir</a>
    </div>

    <div class="mt-4">
        @forelse ($recent as $parcel)
            <a href="{{ route('app.colis.show', $parcel->reference) }}" wire:navigate
               class="flex items-center justify-between gap-4 border-b border-line py-4 transition-colors hover:bg-tk-50">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-tk-700"><x-lucide-package class="h-5 w-5" /></span>
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $parcel->reference }}</p>
                        <p class="text-xs text-muted">{{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
                    </div>
                </div>
                <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
            </a>
        @empty
            <x-tk.empty-state icon="package-open" title="Aucun colis pour l'instant">
                Vos colis apparaîtront ici. Démarrez par une demande d'expédition.
                <x-slot:action>
                    <x-tk.button href="{{ route('app.demandes.create') }}" variant="accent" size="sm" icon="plus">Nouvelle demande</x-tk.button>
                </x-slot:action>
            </x-tk.empty-state>
        @endforelse
    </div>
</div>
