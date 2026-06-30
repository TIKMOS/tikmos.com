<?php

use App\Models\Lead;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public function markHandled(int $id): void
    {
        Lead::findOrFail($id)->update(['handled_at' => now()]);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['leads' => Lead::latest()->paginate(15)];
    }
}; ?>

<div>
    <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Messages</h1>

    <div class="mt-6 space-y-3">
        @forelse ($leads as $lead)
            <x-tk.card wire:key="l-{{ $lead->id }}" :class="$lead->handled_at ? 'opacity-60' : ''">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-content">{{ $lead->name }}</p>
                        <p class="text-xs text-muted">{{ $lead->email ?? '' }}{{ $lead->email && $lead->phone ? ' · ' : '' }}{{ $lead->phone ?? '' }} · {{ $lead->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($lead->handled_at)
                        <x-tk.badge tone="success" dot>Traité</x-tk.badge>
                    @else
                        <button wire:click="markHandled({{ $lead->id }})" class="text-sm font-medium text-content hover:underline">Marquer traité</button>
                    @endif
                </div>
                <p class="mt-3 text-sm text-content/80">{{ $lead->message }}</p>
            </x-tk.card>
        @empty
            <x-tk.empty-state icon="mail" title="Aucun message">Les messages du formulaire de contact arrivent ici.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($leads->hasPages())<div class="mt-6">{{ $leads->links() }}</div>@endif
</div>
