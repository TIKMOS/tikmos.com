<?php

use App\Enums\ParcelStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ParcelService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Parcel $parcel;

    public string $newStatus = '';

    public string $statusComment = '';

    public string $statusLocation = '';

    public ?int $agent_id = null;

    public string $payment_status = '';

    public function mount(string $reference): void
    {
        $this->parcel = Parcel::with(['events', 'sender', 'agent'])->where('reference', $reference)->firstOrFail();
        $this->agent_id = $this->parcel->agent_id;
        $this->payment_status = $this->parcel->payment_status->value;
    }

    public function advance(ParcelService $service): void
    {
        abort_unless(auth()->user()->can('advance', $this->parcel), 403);
        $this->validate(['newStatus' => ['required', Rule::enum(ParcelStatus::class)]]);

        try {
            $service->advanceTo($this->parcel, ParcelStatus::from($this->newStatus), auth()->user(), $this->statusComment ?: null, $this->statusLocation ?: null);
        } catch (InvalidArgumentException $e) {
            $this->addError('newStatus', $e->getMessage());

            return;
        }

        $this->parcel = $this->parcel->fresh(['events', 'sender', 'agent']);
        $this->reset(['newStatus', 'statusComment', 'statusLocation']);
        $this->dispatch('sz-toast', type: 'success', message: 'Statut mis à jour. Client notifié.');
    }

    public function saveDetails(): void
    {
        abort_unless(auth()->user()->can('update', $this->parcel), 403);
        $this->validate([
            'agent_id' => ['nullable', 'exists:users,id'],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
        ]);

        $this->parcel->update(['agent_id' => $this->agent_id ?: null, 'payment_status' => $this->payment_status]);
        $this->dispatch('sz-toast', type: 'success', message: 'Colis mis à jour.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'nextStates' => $this->parcel->status->nextStates(),
            'agents' => User::role(array_map(fn (Role $r) => $r->value, Role::management()))->orderBy('name')->get(),
            'payments' => PaymentStatus::cases(),
        ];
    }
}; ?>

<div>
    <a href="{{ route('admin.colis') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Colis</a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">{{ $parcel->reference }}</h1>
            <p class="mt-1 text-muted">{{ $parcel->recipient_name }} · {{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
        </div>
        <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-tk.card>
                <x-tk.kicker>Suivi</x-tk.kicker>
                <div class="mt-5"><x-tk.stepper :steps="$parcel->timelineSteps()" /></div>
            </x-tk.card>

            @can('advance', $parcel)
                <x-tk.card>
                    <h2 class="font-display text-lg font-bold text-content">Faire avancer le statut</h2>
                    @if (count($nextStates) > 0)
                        <form wire:submit="advance" class="mt-4 space-y-4">
                            <x-tk.field label="Nouveau statut" for="ns" :error="$errors->first('newStatus')">
                                <select wire:model="newStatus" id="ns" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                                    <option value="">Choisir…</option>
                                    @foreach ($nextStates as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                                </select>
                            </x-tk.field>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-tk.field label="Lieu (optionnel)" for="sl"><x-tk.input wire:model="statusLocation" id="sl" icon="map-pin" placeholder="Paris, Cotonou…" /></x-tk.field>
                                <x-tk.field label="Commentaire (optionnel)" for="sc"><x-tk.input wire:model="statusComment" id="sc" icon="message-square" /></x-tk.field>
                            </div>
                            <x-tk.button type="submit" variant="accent">Mettre à jour &amp; notifier</x-tk.button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-muted">Ce colis est à un statut final ({{ $parcel->status->label() }}).</p>
                    @endif
                </x-tk.card>
            @endcan
        </div>

        <div class="space-y-4">
            <x-tk.card>
                <x-tk.kicker>Gestion</x-tk.kicker>
                <form wire:submit="saveDetails" class="mt-4 space-y-4">
                    <x-tk.field label="Agent assigné" for="ag">
                        <select wire:model="agent_id" id="ag" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            <option value="">Non assigné</option>
                            @foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
                        </select>
                    </x-tk.field>
                    <x-tk.field label="Paiement" for="ps">
                        <select wire:model="payment_status" id="ps" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] focus:border-ink focus:outline-none">
                            @foreach ($payments as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>
                    @can('update', $parcel)<x-tk.button type="submit" variant="primary" size="sm">Enregistrer</x-tk.button>@endcan
                </form>
            </x-tk.card>

            <x-tk.card>
                <x-tk.kicker>Client</x-tk.kicker>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-muted">Expéditeur</dt><dd class="font-medium text-content">{{ $parcel->sender?->name ?? $parcel->sender_name ?? 'Walk-in' }}</dd></div>
                    @if ($parcel->weight_kg)<div class="flex justify-between gap-3"><dt class="text-muted">Poids</dt><dd class="font-medium text-content">{{ $parcel->weight_kg }} kg</dd></div>@endif
                    @if ($parcel->price)<div class="flex justify-between gap-3"><dt class="text-muted">Prix</dt><dd class="font-medium text-content">{{ number_format((float) $parcel->price, 0, ',', ' ') }} {{ $parcel->currency }}</dd></div>@endif
                </dl>
            </x-tk.card>
        </div>
    </div>
</div>
