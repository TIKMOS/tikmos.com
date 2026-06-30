<?php

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Parcel::forUser(auth()->user())->latest();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('reference', 'like', '%'.$this->search.'%')
                    ->orWhere('recipient_name', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return [
            'parcels' => $query->paginate(10),
            'statuses' => ParcelStatus::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Espace client</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Mes colis</h1>
        </div>
        <x-tk.button href="{{ route('app.demandes.create') }}" variant="accent" icon="plus">Nouvelle demande</x-tk.button>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <x-tk.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Référence ou destinataire…" aria-label="Rechercher" />
        </div>
        <select wire:model.live="status" aria-label="Filtrer par statut"
                class="min-h-[48px] rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-6" wire:loading.delay.class="opacity-50">
        @forelse ($parcels as $parcel)
            <a href="{{ route('app.colis.show', $parcel->reference) }}" wire:navigate wire:key="parcel-{{ $parcel->id }}"
               class="flex items-center justify-between gap-4 border-b border-line py-4 transition-colors hover:bg-tk-50">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-tk-700"><x-lucide-package class="h-5 w-5" /></span>
                    <div>
                        <p class="font-mono text-sm font-bold text-content">{{ $parcel->reference }}</p>
                        <p class="text-xs text-muted">{{ $parcel->recipient_name }} · {{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
                    <x-lucide-chevron-right class="hidden h-5 w-5 text-tk-400 sm:block" />
                </div>
            </a>
        @empty
            <x-tk.empty-state icon="package-search" title="Aucun colis trouvé">
                Aucun colis ne correspond à votre recherche.
            </x-tk.empty-state>
        @endforelse
    </div>

    @if ($parcels->hasPages())
        <div class="mt-6">{{ $parcels->links() }}</div>
    @endif
</div>
