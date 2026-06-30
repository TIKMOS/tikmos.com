<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'notifications' => auth()->user()->notifications()->latest()->take(30)->get(),
            'unread' => auth()->user()->unreadNotifications()->count(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Alertes</h1>
        </div>
        @if ($unread > 0)
            <x-tk.button wire:click="markAllRead" variant="secondary" size="sm" icon="check">Tout marquer comme lu</x-tk.button>
        @endif
    </div>

    <div class="mt-6">
        @forelse ($notifications as $notification)
            <div wire:key="notif-{{ $notification->id }}"
                 class="flex items-start gap-3 border-b border-line py-4 {{ $notification->read_at ? '' : 'bg-sky/50' }}">
                <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-tile {{ $notification->read_at ? 'bg-tk-100 text-tk-500' : 'bg-ink text-paper' }}">
                    <x-lucide-bell class="h-[18px] w-[18px]" />
                </span>
                <div class="flex-1">
                    <p class="text-sm text-content">{{ $notification->data['message'] ?? 'Notification' }}</p>
                    <p class="mt-0.5 text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @if (! empty($notification->data['parcel_reference']))
                    <a href="{{ route('app.colis.show', $notification->data['parcel_reference']) }}" wire:navigate class="text-sm font-medium text-content hover:underline">Voir</a>
                @endif
            </div>
        @empty
            <x-tk.empty-state icon="bell-off" title="Aucune alerte">
                Vous serez notifié ici dès qu'un colis change de statut.
            </x-tk.empty-state>
        @endforelse
    </div>
</div>
