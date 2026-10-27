<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    public string $closurePassword = '';

    public bool $closureConfirmed = false;

    public function mount(): void
    {
        $user = $this->accountUser();
        $this->name = $user->name;
        $this->phone = $user->phone ?? '';
        $this->city = $user->city ?? '';
    }

    public function save(): void
    {
        $this->accountUser()->update($this->validate());
        $this->dispatch('tk-toast', type: 'success', message: 'Profil mis à jour.');
    }

    public function revokeDevice(int $id): void
    {
        $this->accountUser()->trustedDevices()->whereKey($id)->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Appareil révoqué.');
    }

    public function deleteAccount(): void
    {
        $user = $this->accountUser();
        try {
            $this->validate([
                'closurePassword' => ['required', 'string', 'current_password:web'],
                'closureConfirmed' => ['accepted'],
            ], [
                'closurePassword.required' => 'Saisissez votre mot de passe actuel pour confirmer la fermeture.',
                'closurePassword.current_password' => 'Le mot de passe actuel est incorrect.',
                'closureConfirmed.accepted' => 'Confirmez que vous avez compris la fermeture de l’accès et la conservation des données.',
            ]);
        } catch (ValidationException $exception) {
            $this->closurePassword = '';
            $this->dispatch('closure-error');
            throw $exception;
        }
        $user->delete();
        Auth::logout();
        $this->reset(['closurePassword', 'closureConfirmed']);
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('account_closed', true);

        $this->redirectRoute('login', navigate: true);
    }

    public function cancelClosure(): void
    {
        $this->accountUser();
        $this->reset(['closurePassword', 'closureConfirmed']);
        $this->resetValidation(['closurePassword', 'closureConfirmed']);
    }

    private function accountUser(): User
    {
        // Ne pas se fier à un modèle d'authentification chargé avant la fermeture.
        $user = User::query()->find(Auth::id());
        abort_unless($user instanceof User && $user->isActive(), 403);
        Auth::guard('web')->setUser($user);

        return $user;
    }

    /** RGPD — portabilité : téléchargement de mes données au format JSON. */
    public function exportData(): StreamedResponse
    {
        $user = $this->accountUser();

        $data = [
            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
                'telephone' => $user->phone,
                'ville' => $user->city,
                'inscrit_le' => $user->created_at?->toDateTimeString(),
            ],
            'demandes' => ServiceRequest::forUser($user)->with('quote')->get()->map(fn (ServiceRequest $r) => [
                'reference' => $r->reference,
                'service' => $r->service?->title,
                'statut' => $r->status->label(),
                'message' => $r->message,
                'devis' => $r->quote ? ['reference' => $r->quote->reference, 'montant' => $r->quote->amount, 'statut' => $r->quote->status->label()] : null,
                'cree_le' => $r->created_at?->toDateTimeString(),
            ])->all(),
            'commandes' => Order::where('user_id', $user->id)->with('items')->get()->map(fn (Order $o) => [
                'numero' => $o->number,
                'statut' => $o->status->label(),
                'total' => $o->total,
                'articles' => $o->items->map(fn (OrderItem $i) => ['nom' => $i->name, 'taille' => $i->size, 'quantite' => $i->quantity])->all(),
            ])->all(),
        ];

        $json = (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print ($json), 'mes-donnees-tikmos.json', ['Content-Type' => 'application/json']);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $user = $this->accountUser();

        return [
            'devices' => $user->trustedDevices()->valid()->latest()->get(),
            'isStaff' => $user->isStaff(),
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

    {{-- Fermeture de l'accès, distincte d'une demande d'effacement --}}
    <x-tk.card class="mt-6 border-danger/30" x-data="{ confirm: false }"
        x-on:closure-error.window="$nextTick(() => $refs.closureError?.focus())">
        <h2 class="font-display text-lg font-bold text-content">Fermer mon compte</h2>
        <x-tk.account-closure-info class="mt-3" />
        <div class="mt-4">
            <button type="button" x-ref="closureOpen" x-show="!confirm" x-bind:aria-expanded="confirm" aria-controls="account-closure-form"
                @click="confirm = true; $nextTick(() => $refs.closurePassword.focus())"
                class="min-h-11 text-sm font-medium text-danger underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo">Préparer la fermeture de mon compte</button>
            <form id="account-closure-form" wire:submit="deleteAccount" x-show="confirm" x-cloak class="space-y-4">
                <p class="text-sm font-medium text-content">Confirmez la fermeture de votre accès.</p>
                <x-tk.field label="Mot de passe actuel" for="closure-password">
                    <x-tk.input id="closure-password" x-ref="closurePassword" type="password" wire:model="closurePassword" autocomplete="current-password"
                        :invalid="$errors->has('closurePassword')" aria-describedby="closure-help{{ $errors->has('closurePassword') || $errors->has('closureConfirmed') ? ' closure-error' : '' }}" aria-invalid="{{ $errors->has('closurePassword') ? 'true' : 'false' }}" />
                </x-tk.field>
                <p id="closure-help" class="text-xs text-muted">Le mot de passe n’est pas modifié. Vous serez déconnecté après confirmation.</p>
                <label class="flex min-h-11 items-start gap-3 text-sm text-content">
                    <input type="checkbox" wire:model="closureConfirmed" class="mt-1 h-4 w-4 shrink-0 rounded border-tk-300 text-ink focus:ring-ink/20"
                        aria-describedby="{{ $errors->has('closurePassword') || $errors->has('closureConfirmed') ? 'closure-error' : 'closure-help' }}" aria-invalid="{{ $errors->has('closureConfirmed') ? 'true' : 'false' }}">
                    <span>Je comprends que mon accès sera désactivé et que mes données ne seront pas automatiquement effacées.</span>
                </label>
                @if ($errors->has('closurePassword') || $errors->has('closureConfirmed'))
                    <div id="closure-error" x-ref="closureError" role="alert" tabindex="-1" class="text-sm font-medium text-danger">
                        @foreach (['closurePassword', 'closureConfirmed'] as $field)
                            @error($field)<p>{{ $message }}</p>@enderror
                        @endforeach
                    </div>
                @endif
                <div class="flex flex-wrap items-center gap-3">
                    <x-tk.button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="deleteAccount">Confirmer la fermeture</x-tk.button>
                    <button type="button" wire:click="cancelClosure" @click="confirm = false; $nextTick(() => $refs.closureOpen.focus())"
                        wire:loading.attr="disabled" wire:target="deleteAccount" class="min-h-11 px-3 text-sm font-medium text-content underline underline-offset-2">Annuler</button>
                </div>
            </form>
        </div>
    </x-tk.card>
</div>
