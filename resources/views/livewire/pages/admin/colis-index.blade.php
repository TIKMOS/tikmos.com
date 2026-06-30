<?php

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Services\ParcelService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showCreate = false;

    #[Validate('required|string|max:255')]
    public string $recipient_name = '';

    #[Validate('nullable|string|max:30')]
    public string $recipient_phone = '';

    #[Validate('required|string')]
    public string $type = 'vetements';

    #[Validate('required|in:cotonou,paris')]
    public string $origin = 'cotonou';

    #[Validate('required|in:cotonou,paris')]
    public string $destination = 'paris';

    #[Validate('nullable|numeric|min:0|max:200')]
    public ?float $weight_kg = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $price = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(ParcelService $service): void
    {
        $this->authorize('create', Parcel::class);
        $data = $this->validate();

        $service->create([...$data, 'agent_id' => auth()->id()]);

        $this->reset(['recipient_name', 'recipient_phone', 'weight_kg', 'price', 'showCreate']);
        $this->dispatch('sz-toast', type: 'success', message: 'Colis créé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Parcel::with('sender')->latest();

        if ($this->search !== '') {
            $query->where(fn ($q) => $q->where('reference', 'like', '%'.$this->search.'%')->orWhere('recipient_name', 'like', '%'.$this->search.'%'));
        }
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return ['parcels' => $query->paginate(15), 'statuses' => ParcelStatus::cases()];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Colis</h1>
        </div>
        @can('colis.create')
            <x-tk.button wire:click="$set('showCreate', true)" variant="accent" icon="plus">Nouveau colis</x-tk.button>
        @endcan
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <div class="flex-1"><x-tk.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Référence ou destinataire…" /></div>
        <select wire:model.live="status" class="min-h-[48px] rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
        </select>
    </div>

    <div class="mt-6" wire:loading.delay.class="opacity-50">
        @forelse ($parcels as $parcel)
            <a href="{{ route('admin.colis.show', $parcel->reference) }}" wire:navigate wire:key="p-{{ $parcel->id }}" class="flex items-center justify-between gap-4 border-b border-line py-3.5 hover:bg-tk-50">
                <div>
                    <p class="font-mono text-sm font-bold text-content">{{ $parcel->reference }}</p>
                    <p class="text-xs text-muted">{{ $parcel->recipient_name }} · {{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-tk.badge :tone="$parcel->payment_status->tone()">{{ $parcel->payment_status->label() }}</x-tk.badge>
                    <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
                </div>
            </a>
        @empty
            <x-tk.empty-state icon="package-search" title="Aucun colis">Aucun colis ne correspond.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($parcels->hasPages())<div class="mt-6">{{ $parcels->links() }}</div>@endif

    {{-- Modal création --}}
    <div x-show="$wire.showCreate" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showCreate', false)">
        <div @click.outside="$wire.set('showCreate', false)" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-content">Nouveau colis</h2>
                <button @click="$wire.set('showCreate', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
            </div>
            <form wire:submit="create" class="mt-5 space-y-4">
                <x-tk.field label="Destinataire" for="rn" :error="$errors->first('recipient_name')"><x-tk.input wire:model="recipient_name" id="rn" icon="user" /></x-tk.field>
                <x-tk.field label="Téléphone" :error="$errors->first('recipient_phone')"><x-tk.phone wire:model="recipient_phone" :invalid="$errors->has('recipient_phone')" /></x-tk.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-tk.field label="Type" for="ty">
                        <select wire:model="type" id="ty" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            @foreach (App\Enums\ParcelType::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-tk.field label="Départ" for="og">
                        <select wire:model="origin" id="og" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            @foreach (App\Enums\ParcelOrigin::cases() as $o)<option value="{{ $o->value }}">{{ $o->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>
                    <x-tk.field label="Destination" for="de">
                        <select wire:model="destination" id="de" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            @foreach (App\Enums\ParcelOrigin::cases() as $o)<option value="{{ $o->value }}">{{ $o->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-tk.field label="Poids (kg)" for="wk"><x-tk.input wire:model="weight_kg" id="wk" type="number" step="0.1" icon="weight" /></x-tk.field>
                    <x-tk.field label="Prix (XOF)" for="pr"><x-tk.input wire:model="price" id="pr" type="number" icon="banknote" /></x-tk.field>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-tk.button type="submit" variant="accent">Créer le colis</x-tk.button>
                    <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showCreate', false)">Annuler</x-tk.button>
                </div>
            </form>
        </div>
    </div>
</div>
