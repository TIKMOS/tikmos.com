<?php

use App\Enums\ServiceRequestStatus;
use App\Models\Order;
use App\Models\ServiceRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        $user = auth()->user();

        return [
            'openRequests' => ServiceRequest::forUser($user)
                ->whereNotIn('status', [ServiceRequestStatus::Delivered->value, ServiceRequestStatus::Refused->value, ServiceRequestStatus::Cancelled->value])
                ->count(),
            'quotedCount' => ServiceRequest::forUser($user)->where('status', ServiceRequestStatus::Quoted->value)->count(),
            'ordersCount' => Order::where('user_id', $user->id)->count(),
            'unread' => $user->unreadNotifications()->count(),
            'recent' => ServiceRequest::forUser($user)->with('service')->latest()->take(4)->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Bonjour {{ auth()->user()->name }}</h1>
        </div>
        <x-tk.button href="{{ url('/services') }}" variant="primary" icon="plus">Demander un devis</x-tk.button>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-tk.stat label="Demandes en cours" :value="$openRequests" icon="file-text" tint="sky" />
        <x-tk.stat label="Devis à traiter" :value="$quotedCount" icon="receipt" tint="mint" />
        <x-tk.stat label="Mes commandes" :value="$ordersCount" icon="shopping-bag" tint="lilac" />
        <x-tk.stat label="Alertes" :value="$unread" icon="bell" tint="peach" />
    </div>

    <div class="mt-8 flex items-center justify-between">
        <x-tk.kicker>Mes demandes récentes</x-tk.kicker>
        <a href="{{ route('app.requests') }}" wire:navigate class="text-sm font-medium text-content hover:underline">Tout voir</a>
    </div>

    <div class="mt-4">
        @forelse ($recent as $request)
            <a href="{{ route('app.requests.show', $request->reference) }}" wire:navigate
               class="flex items-center justify-between gap-4 border-b border-line py-4 transition-colors hover:bg-tk-50">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-tk-700"><x-lucide-file-text class="h-5 w-5" /></span>
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $request->reference }}</p>
                        <p class="text-xs text-muted">{{ $request->service?->title ?? 'Demande sur mesure' }}</p>
                    </div>
                </div>
                <x-tk.badge :tone="$request->status->tone()" dot>{{ $request->status->label() }}</x-tk.badge>
            </a>
        @empty
            <x-tk.empty-state icon="file-text" title="Aucune demande pour l'instant">
                Décrivez votre besoin pour recevoir un devis.
                <x-slot:action><x-tk.button href="{{ url('/services') }}" variant="primary" icon="arrow-right">Voir les services</x-tk.button></x-slot:action>
            </x-tk.empty-state>
        @endforelse
    </div>
</div>
