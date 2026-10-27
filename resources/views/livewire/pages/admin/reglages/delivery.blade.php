<?php

use App\Enums\DeliveryPricing;
use App\Settings\DeliverySettings;
use App\Settings\ShopSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public string $mode = 'unknown';

    public string $fee = '0';

    public function mount(ShopSettings $settings): void
    {
        abort_unless(auth()->user()?->can('settings.manage'), 403);
        $this->mode = $settings->deliveryPricing()->value;
        $this->fee = (string) $settings->default_delivery_fee;
    }

    public function save(ShopSettings $settings): void
    {
        abort_unless(auth()->user()?->can('settings.manage'), 403);
        $this->validate(['mode' => ['required', Rule::enum(DeliveryPricing::class)],
            'fee' => $this->mode === 'fixed' ? ['required', 'integer', 'min:1', 'max:2147483647'] : ['exclude']]);
        DB::transaction(function () use ($settings): void {
            $delivery = app(DeliverySettings::class);
            $delivery->mode = $this->mode;
            $delivery->save();
            $settings->default_delivery_fee = $this->mode === 'fixed' ? (int) $this->fee : 0;
            $settings->save();
        });
        $this->fee = (string) $settings->default_delivery_fee;
        $this->dispatch('tk-toast', type: 'success', message: 'Conditions de livraison enregistrées pour les nouvelles commandes.');
    }
}; ?>

<div class="max-w-2xl">
    <h1 class="font-display text-3xl font-bold text-content">Livraison de la boutique</h1>
    <p class="mt-3 text-muted">Ces réglages concernent les nouvelles commandes. Ils ne modifient pas les montants ou paiements des commandes existantes.</p>
    <form wire:submit="save" class="mt-6 space-y-5 rounded-card border border-line bg-surface p-5 sm:p-6">
        <x-tk.field label="Tarification de la livraison" for="delivery-mode" :error="$errors->first('mode')">
            <select id="delivery-mode" wire:model.live="mode" aria-describedby="delivery-mode-help" class="min-h-11 w-full rounded-input border border-tk-300 bg-surface px-3 text-content">
                <option value="unknown">Frais à déterminer avec le client</option>
                <option value="free">Livraison gratuite</option>
                <option value="fixed">Tarif fixe connu</option>
            </select>
        </x-tk.field>
        <p id="delivery-mode-help" class="text-sm text-muted">La gratuité doit être choisie explicitement. Si les frais restent inconnus, le paiement en ligne est indisponible ; l’équipe doit communiquer le montant final et obtenir l’accord du client.</p>
        @if ($mode === 'fixed')
            <x-tk.field label="Frais de livraison en FCFA" for="delivery-fee" :error="$errors->first('fee')">
                <x-tk.input id="delivery-fee" type="number" min="1" step="1" wire:model="fee" :invalid="$errors->has('fee')" />
            </x-tk.field>
        @else
            <p class="text-sm text-content">{{ $mode === 'free' ? 'La livraison sera annoncée gratuite, avec zéro frais.' : 'Le récapitulatif annoncera un total hors livraison, sans frais inventés.' }}</p>
        @endif
        <x-tk.button type="submit" wire:loading.attr="disabled" wire:target="save">Enregistrer la livraison</x-tk.button>
    </form>
</div>
