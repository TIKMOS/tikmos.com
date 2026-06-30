<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|string')]
    public string $type = 'vetements';

    #[Validate('required|in:cotonou,paris')]
    public string $origin = 'cotonou';

    #[Validate('required|in:cotonou,paris')]
    public string $destination = 'paris';

    #[Validate('nullable|numeric|min:0|max:200')]
    public ?float $weight_estimate = null;

    #[Validate('nullable|string|max:255')]
    public string $recipient_name = '';

    #[Validate('nullable|string|max:30')]
    public string $recipient_phone = '';

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    public function submit(): void
    {
        $data = $this->validate();

        if ($this->origin === $this->destination) {
            $this->addError('destination', 'L\'origine et la destination doivent être différentes.');

            return;
        }

        auth()->user()->parcelRequests()->create([...$data, 'status' => 'new']);

        session()->flash('toast', ['type' => 'success', 'message' => 'Demande envoyée. Nous revenons vers vous très vite.']);
        $this->redirectRoute('app.dashboard', navigate: true);
    }
}; ?>

<div>
    <a href="{{ route('app.dashboard') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content">
        <x-lucide-arrow-left class="h-4 w-4" /> Tableau de bord
    </a>

    <div class="mt-4 max-w-2xl">
        <x-tk.kicker>Nouvelle demande</x-tk.kicker>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Demander une expédition</h1>
        <p class="mt-2 text-muted">Décrivez votre envoi : nous vous recontactons avec un devis.</p>

        <form wire:submit="submit" class="mt-8 space-y-5">
            <x-tk.field label="Type de colis" for="type" :error="$errors->first('type')">
                <select wire:model="type" id="type"
                        class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                    @foreach (App\Enums\ParcelType::cases() as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
            </x-tk.field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-tk.field label="Départ" for="origin" :error="$errors->first('origin')">
                    <select wire:model="origin" id="origin" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                        @foreach (App\Enums\ParcelOrigin::cases() as $o)<option value="{{ $o->value }}">{{ $o->label() }}</option>@endforeach
                    </select>
                </x-tk.field>
                <x-tk.field label="Destination" for="destination" :error="$errors->first('destination')">
                    <select wire:model="destination" id="destination" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                        @foreach (App\Enums\ParcelOrigin::cases() as $o)<option value="{{ $o->value }}">{{ $o->label() }}</option>@endforeach
                    </select>
                </x-tk.field>
            </div>

            <x-tk.field label="Poids estimé (kg)" for="weight" hint="Optionnel" :error="$errors->first('weight_estimate')">
                <x-tk.input wire:model="weight_estimate" id="weight" type="number" step="0.1" icon="weight" placeholder="3.5" :invalid="$errors->has('weight_estimate')" />
            </x-tk.field>

            <x-tk.field label="Destinataire" for="rname" :error="$errors->first('recipient_name')">
                <x-tk.input wire:model="recipient_name" id="rname" icon="user" placeholder="Nom du destinataire" />
            </x-tk.field>
            <x-tk.field label="Téléphone destinataire" :error="$errors->first('recipient_phone')">
                <x-tk.phone wire:model="recipient_phone" :invalid="$errors->has('recipient_phone')" />
            </x-tk.field>

            <x-tk.field label="Message (optionnel)" for="message" :error="$errors->first('message')">
                <textarea wire:model="message" id="message" rows="4" placeholder="Précisions sur votre envoi…"
                          class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content placeholder:text-tk-400 focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
            </x-tk.field>

            <div class="flex gap-3">
                <x-tk.button type="submit" variant="accent" size="lg">Envoyer la demande</x-tk.button>
                <x-tk.button href="{{ route('app.dashboard') }}" variant="ghost" size="lg">Annuler</x-tk.button>
            </div>
        </form>
    </div>
</div>
