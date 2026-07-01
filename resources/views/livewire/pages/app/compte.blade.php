<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->phone = $user->phone ?? '';
        $this->city = $user->city ?? '';
    }

    public function save(): void
    {
        auth()->user()->update($this->validate());
        $this->dispatch('sz-toast', type: 'success', message: 'Profil mis à jour.');
    }

    public function revokeDevice(int $id): void
    {
        auth()->user()->trustedDevices()->whereKey($id)->delete();
        $this->dispatch('sz-toast', type: 'success', message: 'Appareil révoqué.');
    }

    public function deleteAccount(): void
    {
        $user = auth()->user();
        Auth::logout();
        $user->delete();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: true);
    }

    /** RGPD — portabilité : téléchargement de mes données au format JSON. */
    public function exportData(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();

        $data = [
            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
                'telephone' => $user->phone,
                'ville' => $user->city,
                'inscrit_le' => $user->created_at?->toDateTimeString(),
            ],
            'demandes' => \App\Models\ServiceRequest::forUser($user)->with('quote')->get()->map(fn ($r) => [
                'reference' => $r->reference,
                'service' => $r->service?->title,
                'statut' => $r->status->label(),
                'message' => $r->message,
                'devis' => $r->quote ? ['reference' => $r->quote->reference, 'montant' => $r->quote->amount, 'statut' => $r->quote->status->label()] : null,
                'cree_le' => $r->created_at?->toDateTimeString(),
            ])->all(),
            'commandes' => \App\Models\Order::where('user_id', $user->id)->with('items')->get()->map(fn ($o) => [
                'numero' => $o->number,
                'statut' => $o->status->label(),
                'total' => $o->total,
                'articles' => $o->items->map(fn ($i) => ['nom' => $i->name, 'taille' => $i->size, 'quantite' => $i->quantity])->all(),
            ])->all(),
        ];

        $json = (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print($json), 'mes-donnees-tikmos.json', ['Content-Type' => 'application/json']);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'devices' => auth()->user()->trustedDevices()->valid()->latest()->get(),
            'isStaff' => auth()->user()->isStaff(),
        ];
    }
}; ?>

<div class="max-w-2xl">
    <x-tk.kicker>Espace client</x-tk.kicker>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Mon compte</h1>

    {{-- Profil --}}
    <x-tk.card class="mt-8">
        <h2 class="font-display text-lg font-bold text-content">Profil</h2>
        <form wire:submit="save" class="mt-5 space-y-5">
            <x-tk.field label="Nom complet" for="name" :error="$errors->first('name')">
                <x-tk.input wire:model="name" id="name" icon="user" :invalid="$errors->has('name')" />
            </x-tk.field>
            <x-tk.field label="Téléphone" :error="$errors->first('phone')">
                <x-tk.phone wire:model="phone" :invalid="$errors->has('phone')" />
            </x-tk.field>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-tk.field label="Ville" for="city" :error="$errors->first('city')">
                    <x-tk.input wire:model="city" id="city" icon="map-pin" />
                </x-tk.field>
                <x-tk.field label="Email">
                    <x-tk.input value="{{ auth()->user()->email }}" disabled icon="mail" class="opacity-60" />
                </x-tk.field>
            </div>
            <x-tk.button type="submit" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </x-tk.button>
        </form>
    </x-tk.card>

    {{-- Sécurité --}}
    <x-tk.card class="mt-6">
        <h2 class="font-display text-lg font-bold text-content">Sécurité</h2>
        <div class="mt-4 flex items-center justify-between gap-4 rounded-tile bg-tk-50 px-4 py-3">
            <div class="flex items-center gap-2 text-sm">
                <x-lucide-shield-check class="h-5 w-5 {{ $isStaff ? 'text-success' : 'text-tk-400' }}" />
                <span class="text-content">Double authentification (2FA)</span>
            </div>
            <x-tk.badge :tone="$isStaff ? 'success' : 'neutral'">{{ $isStaff ? 'Activée' : 'Non requise' }}</x-tk.badge>
        </div>

        <p class="mt-5 text-sm font-medium text-content">Appareils de confiance</p>
        <div class="mt-2">
            @forelse ($devices as $device)
                <div wire:key="device-{{ $device->id }}" class="flex items-center justify-between gap-4 border-b border-line py-3 text-sm">
                    <div>
                        <p class="text-content">{{ \Illuminate\Support\Str::limit($device->user_agent, 50) ?: 'Appareil inconnu' }}</p>
                        <p class="text-xs text-muted">Expire {{ $device->expires_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="revokeDevice({{ $device->id }})" class="text-sm font-medium text-danger hover:underline">Révoquer</button>
                </div>
            @empty
                <p class="text-sm text-muted">Aucun appareil de confiance enregistré.</p>
            @endforelse
        </div>
    </x-tk.card>

    {{-- Application --}}
    <x-tk.card class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-lg font-bold text-content">Installer l'application</h2>
                <p class="mt-1 text-sm text-muted">Accédez à {{ config('app.name') }} depuis votre écran d'accueil, comme une vraie app.</p>
            </div>
            <x-tk.install-button variant="accent" />
        </div>
    </x-tk.card>

    {{-- Mes données (RGPD) --}}
    <x-tk.card class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-lg font-bold text-content">Mes données</h2>
                <p class="mt-1 text-sm text-muted">Téléchargez une copie de vos données (profil, demandes, commandes) au format JSON.</p>
            </div>
            <x-tk.button wire:click="exportData" variant="secondary" icon="download">Exporter mes données</x-tk.button>
        </div>
    </x-tk.card>

    {{-- Zone danger --}}
    <x-tk.card class="mt-6 border-danger/30" x-data="{ confirm: false }">
        <h2 class="font-display text-lg font-bold text-danger">Supprimer mon compte</h2>
        <p class="mt-2 text-sm text-muted">Cette action supprime définitivement votre compte et vos accès, conformément à votre droit à l'effacement.</p>
        <div class="mt-4">
            <button x-show="!confirm" @click="confirm = true" class="text-sm font-medium text-danger hover:underline">Je veux supprimer mon compte</button>
            <div x-show="confirm" x-cloak class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-content">Êtes-vous sûr ?</span>
                <x-tk.button wire:click="deleteAccount" variant="danger" size="sm">Oui, supprimer</x-tk.button>
                <button @click="confirm = false" class="text-sm text-muted hover:text-content">Annuler</button>
            </div>
        </div>
    </x-tk.card>
</div>
