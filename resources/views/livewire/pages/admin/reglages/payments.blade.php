<?php

use App\Settings\PaymentSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public string $mode = 'cod';

    public bool $kkiapay_sandbox = true;

    public string $kkiapay_public_key = '';

    public string $kkiapay_private_key = '';

    public string $kkiapay_secret = '';

    public function mount(PaymentSettings $s): void
    {
        $this->mode = $s->mode;
        $this->kkiapay_sandbox = $s->kkiapay_sandbox;
        $this->kkiapay_public_key = $s->kkiapay_public_key;
        $this->kkiapay_private_key = $s->kkiapay_private_key;
        $this->kkiapay_secret = $s->kkiapay_secret;
    }

    public function save(PaymentSettings $s): void
    {
        $this->validate([
            'mode' => ['required', 'in:cod,online,both'],
            'kkiapay_sandbox' => ['boolean'],
            'kkiapay_public_key' => ['nullable', 'string', 'max:255'],
            'kkiapay_private_key' => ['nullable', 'string', 'max:255'],
            'kkiapay_secret' => ['nullable', 'string', 'max:255'],
        ]);

        // Exige au moins un mode utilisable.
        if ($this->mode !== 'cod' && trim($this->kkiapay_public_key) === '') {
            $this->addError('kkiapay_public_key', 'La clé publique KKiaPay est requise pour activer le paiement en ligne.');

            return;
        }

        $s->mode = $this->mode;
        $s->kkiapay_sandbox = $this->kkiapay_sandbox;
        $s->kkiapay_public_key = trim($this->kkiapay_public_key);
        $s->kkiapay_private_key = trim($this->kkiapay_private_key);
        $s->kkiapay_secret = trim($this->kkiapay_secret);
        $s->save();

        $this->dispatch('tk-toast', type: 'success', message: 'Réglages de paiement enregistrés.');
    }

    public string $webhookUrl = '';

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'webhookUrl' => route('webhooks.payments', ['provider' => 'kkiapay']),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Paiements</h1>
        </div>
        <x-tk.button wire:click="save" variant="primary" icon="check">
            <span wire:loading.remove wire:target="save">Enregistrer</span>
            <span wire:loading wire:target="save">Enregistrement…</span>
        </x-tk.button>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        {{-- Mode de paiement --}}
        <x-tk.admin-section title="Mode de paiement" description="Comment vos clients règlent leurs commandes en boutique." icon="wallet">
            <div class="space-y-2">
                @foreach ([
                    ['cod', 'Paiement à la livraison', 'Encaissement en espèces ou Mobile Money à la réception.', 'truck'],
                    ['online', 'Paiement en ligne', 'KKiaPay : Mobile Money et carte bancaire, immédiat.', 'smartphone'],
                    ['both', 'Les deux (au choix du client)', 'Le client choisit entre en ligne et à la livraison.', 'layers'],
                ] as [$value, $label, $desc, $icon])
                    <label class="flex cursor-pointer items-center gap-3 rounded-tile border-2 p-4 transition"
                        :class="$wire.mode === '{{ $value }}' ? 'border-ink bg-sky/40' : 'border-line hover:border-tk-300'">
                        <input type="radio" wire:model="mode" value="{{ $value }}" class="sr-only" />
                        <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6 text-content" />
                        <span><span class="block font-medium text-content">{{ $label }}</span><span class="block text-sm text-muted">{{ $desc }}</span></span>
                        <x-lucide-check-circle-2 class="ml-auto h-5 w-5 text-content" x-show="$wire.mode === '{{ $value }}'" />
                    </label>
                @endforeach
            </div>
            @error('mode')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        </x-tk.admin-section>

        {{-- Passerelle KKiaPay --}}
        <x-tk.admin-section title="Passerelle KKiaPay" description="Clés d'API (tableau de bord KKiaPay). Le mode bac à sable n'encaisse pas d'argent réel." icon="key-round">
            <x-tk.toggle label="Mode bac à sable (sandbox)" wire:model="kkiapay_sandbox" />
            <div class="mt-4 space-y-4">
                <x-tk.field label="Clé publique" hint="Utilisée par le widget côté client." :error="$errors->first('kkiapay_public_key')">
                    <x-tk.input wire:model="kkiapay_public_key" placeholder="pk_…" />
                </x-tk.field>
                <x-tk.field label="Clé privée" hint="Chiffrée. Vérification serveur des transactions." :error="$errors->first('kkiapay_private_key')">
                    <x-tk.input wire:model="kkiapay_private_key" type="password" placeholder="••••••••" />
                </x-tk.field>
                <x-tk.field label="Secret" hint="Chiffré. Signature des webhooks." :error="$errors->first('kkiapay_secret')">
                    <x-tk.input wire:model="kkiapay_secret" type="password" placeholder="••••••••" />
                </x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Webhook --}}
        <x-tk.admin-section title="Webhook" description="À renseigner dans le tableau de bord KKiaPay pour la confirmation asynchrone." icon="webhook">
            <div class="flex items-center gap-2 rounded-tile border border-line bg-tk-50 p-3">
                <code class="min-w-0 flex-1 truncate text-sm text-content">{{ $webhookUrl }}</code>
                <button type="button"
                    x-data
                    x-on:click="navigator.clipboard.writeText(@js($webhookUrl)); $dispatch('tk-toast', { type: 'success', message: 'URL copiée.' })"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-content"><x-lucide-copy class="h-4 w-4" /></button>
            </div>
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer les réglages</x-tk.button>
        </div>
    </form>
</div>
