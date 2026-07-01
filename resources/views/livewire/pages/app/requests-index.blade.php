<?php

use App\Models\ServiceRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'requests' => ServiceRequest::forUser(auth()->user())
                ->with(['service', 'quote'])
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Mes demandes & devis</h1>
        </div>
        <x-tk.button href="{{ url('/services') }}" variant="primary" icon="plus">Demander un devis</x-tk.button>
    </div>

    <div class="mt-8">
        @forelse ($requests as $request)
            <a href="{{ route('app.requests.show', $request->reference) }}" wire:navigate wire:key="req-{{ $request->id }}"
               class="flex items-center justify-between gap-4 border-b border-line py-4 transition-colors hover:bg-tk-50">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-tk-700"><x-lucide-file-text class="h-5 w-5" /></span>
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $request->reference }}</p>
                        <p class="text-xs text-muted">{{ $request->service?->title ?? 'Demande sur mesure' }} · {{ $request->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if ($request->quote)<span class="hidden font-display text-sm font-bold text-content sm:block">{{ $request->quote->amountLabel() }}</span>@endif
                    <x-tk.badge :tone="$request->status->tone()" dot>{{ $request->status->label() }}</x-tk.badge>
                </div>
            </a>
        @empty
            <x-tk.empty-state icon="file-text" title="Aucune demande pour l'instant">
                Décrivez votre besoin depuis un service pour recevoir un devis.
                <x-slot:action><x-tk.button href="{{ url('/services') }}" variant="primary" icon="arrow-right">Voir les services</x-tk.button></x-slot:action>
            </x-tk.empty-state>
        @endforelse
    </div>
</div>
