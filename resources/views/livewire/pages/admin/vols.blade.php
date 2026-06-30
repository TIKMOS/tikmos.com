<?php

use App\Enums\FlightDirection;
use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|in:paris_cotonou,cotonou_paris')]
    public string $direction = 'paris_cotonou';

    #[Validate('required|date')]
    public string $departs_on = '';

    #[Validate('nullable|numeric|min:0|max:5000')]
    public ?float $capacity_kg = null;

    #[Validate('required')]
    public string $status = 'scheduled';

    public bool $is_public = true;

    public bool $is_featured = false;

    public string $cutoff_at = '';

    public function newFlight(): void
    {
        $this->reset(['editingId', 'direction', 'departs_on', 'capacity_kg', 'status', 'is_public', 'is_featured', 'cutoff_at']);
        $this->is_public = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $flight = Flight::findOrFail($id);
        $this->editingId = $flight->id;
        $this->direction = $flight->direction->value;
        $this->departs_on = $flight->departs_on->toDateString();
        $this->capacity_kg = $flight->capacity_kg !== null ? (float) $flight->capacity_kg : null;
        $this->status = $flight->status->value;
        $this->is_public = $flight->is_public;
        $this->is_featured = $flight->is_featured;
        $this->cutoff_at = $flight->cutoff_at?->format('Y-m-d\TH:i') ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('vol.manage'), 403);
        $data = $this->validate([
            'direction' => ['required', Rule::enum(FlightDirection::class)],
            'departs_on' => ['required', 'date'],
            'cutoff_at' => ['nullable', 'date'],
            'capacity_kg' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'status' => ['required', Rule::enum(FlightStatus::class)],
            'is_public' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        $data['cutoff_at'] = $this->cutoff_at !== '' ? $this->cutoff_at : null;

        if ($this->editingId) {
            Flight::findOrFail($this->editingId)->update($data);
        } else {
            Flight::create([...$data, 'created_by' => auth()->id()]);
        }

        $this->reset(['showForm', 'editingId']);
        $this->dispatch('sz-toast', type: 'success', message: 'Vol enregistré.');
    }

    public function togglePublish(int $id): void
    {
        abort_unless(auth()->user()->can('vol.manage'), 403);
        $flight = Flight::findOrFail($id);
        $flight->update(['is_public' => ! $flight->is_public]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('vol.manage'), 403);
        Flight::findOrFail($id)->delete();
        $this->dispatch('sz-toast', type: 'success', message: 'Vol supprimé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'flights' => Flight::withCount('parcels')->orderByDesc('departs_on')->paginate(15),
            'directions' => FlightDirection::cases(),
            'statuses' => FlightStatus::cases(),
            'canManage' => auth()->user()->can('vol.manage'),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Vols</h1>
        </div>
        @if ($canManage)<x-tk.button wire:click="newFlight" variant="accent" icon="plus">Nouveau vol</x-tk.button>@endif
    </div>

    <div class="mt-6">
        @forelse ($flights as $flight)
            <div wire:key="f-{{ $flight->id }}" class="flex flex-col gap-3 border-b border-line py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-tile bg-ink text-paper"><x-lucide-plane class="h-5 w-5" /></span>
                    <div>
                        <p class="font-display font-bold text-content">{{ $flight->direction->label() }}</p>
                        <p class="text-sm text-muted">{{ $flight->departs_on->translatedFormat('j F Y') }} · {{ $flight->parcels_count }} colis @if ($flight->capacity_kg) · cap. {{ (int) $flight->capacity_kg }} kg @endif</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pl-15 sm:pl-0">
                    <x-tk.badge :tone="$flight->status->tone()" dot>{{ $flight->status->label() }}</x-tk.badge>
                    @if ($flight->is_public)<x-tk.badge tone="success">Public</x-tk.badge>@else<x-tk.badge tone="neutral">Privé</x-tk.badge>@endif
                    @if ($canManage)
                        <button wire:click="togglePublish({{ $flight->id }})" class="rounded-tile p-2 text-tk-500 hover:bg-tk-100" title="Publier/dépublier"><x-lucide-eye class="h-4 w-4" /></button>
                        <button wire:click="edit({{ $flight->id }})" class="rounded-tile p-2 text-tk-500 hover:bg-tk-100" title="Modifier"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $flight->id }})" wire:confirm="Supprimer ce vol ?" class="rounded-tile p-2 text-danger hover:bg-danger-soft" title="Supprimer"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    @endif
                </div>
            </div>
        @empty
            <x-tk.empty-state icon="plane" title="Aucun vol">Créez votre premier vol.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($flights->hasPages())<div class="mt-6">{{ $flights->links() }}</div>@endif

    {{-- Modal formulaire --}}
    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
        <div @click.outside="$wire.set('showForm', false)" class="w-full max-w-md rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier le vol' : 'Nouveau vol' }}</h2>
                <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
            </div>
            <form wire:submit="save" class="mt-5 space-y-4">
                <x-tk.field label="Sens" for="dir">
                    <select wire:model="direction" id="dir" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                        @foreach ($directions as $d)<option value="{{ $d->value }}">{{ $d->label() }}</option>@endforeach
                    </select>
                </x-tk.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-tk.field label="Date de départ" for="dep" :error="$errors->first('departs_on')"><x-tk.input wire:model="departs_on" id="dep" type="date" /></x-tk.field>
                    <x-tk.field label="Clôture des dépôts" for="cut" hint="Optionnel — fin du compte à rebours" :error="$errors->first('cutoff_at')"><x-tk.input wire:model="cutoff_at" id="cut" type="datetime-local" /></x-tk.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-tk.field label="Capacité (kg)" for="cap"><x-tk.input wire:model="capacity_kg" id="cap" type="number" /></x-tk.field>
                    <x-tk.field label="Statut" for="st">
                        <select wire:model="status" id="st" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-content">
                        <input type="checkbox" wire:model="is_public" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20"> Afficher sur le site public
                    </label>
                    <label class="flex items-center gap-2 text-sm text-content">
                        <input type="checkbox" wire:model="is_featured" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20"> Mettre en avant (carte « prochain départ »)
                    </label>
                </div>
                <div class="flex gap-3 pt-2">
                    <x-tk.button type="submit" variant="accent">Enregistrer</x-tk.button>
                    <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                </div>
            </form>
        </div>
    </div>
</div>
