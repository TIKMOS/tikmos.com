<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $selectedId = null;

    public string $notes = '';

    // Formulaire créer / modifier.
    public bool $showForm = false;

    public ?int $formId = null;

    public string $cName = '';

    public string $cEmail = '';

    public string $cPhone = '';

    public string $cCity = '';

    public string $cPassword = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('clients.manage'), 403);
    }

    #[Computed]
    public function selected(): ?User
    {
        if (! $this->selectedId) {
            return null;
        }

        return User::role(Role::Client->value)
            ->withCount(['serviceRequests', 'orders', 'reviews'])
            ->with(['serviceRequests' => fn ($q) => $q->latest()->take(5)])
            ->find($this->selectedId);
    }

    public function open(int $id): void
    {
        $this->selectedId = $id;
        $this->notes = (string) (User::find($id)?->internal_notes);
    }

    public function close(): void
    {
        $this->reset(['selectedId', 'notes']);
    }

    public function saveNotes(): void
    {
        $this->authorizeManage();
        if ($client = $this->selected) {
            $client->update(['internal_notes' => $this->notes ?: null]);
            $this->dispatch('tk-toast', type: 'success', message: 'Notes enregistrées.');
        }
    }

    public function toggleSuspend(): void
    {
        $this->authorizeManage();
        if ($client = $this->selected) {
            $client->isSuspended() ? $client->reactivate() : $client->suspend();
            unset($this->selected);
            $this->dispatch('tk-toast', type: 'success', message: 'Statut mis à jour.');
        }
    }

    public function deleteClient(): void
    {
        $this->authorizeManage();
        if ($client = $this->selected) {
            $client->delete();
            $this->close();
            $this->dispatch('tk-toast', type: 'success', message: 'Client supprimé.');
        }
    }

    public function openCreate(): void
    {
        $this->authorizeManage();
        $this->reset(['formId', 'cName', 'cEmail', 'cPhone', 'cCity', 'cPassword']);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(): void
    {
        $this->authorizeManage();
        if (! $client = $this->selected) {
            return;
        }
        $this->formId = $client->id;
        $this->cName = $client->name;
        $this->cEmail = (string) $client->email;
        $this->cPhone = (string) $client->phone;
        $this->cCity = (string) $client->city;
        $this->cPassword = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function saveClient(): void
    {
        $this->authorizeManage();
        $isEdit = $this->formId !== null;

        $rules = [
            'cName' => ['required', 'string', 'max:255'],
            'cEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->formId)],
            'cPhone' => ['nullable', 'string', 'max:40'],
            'cCity' => ['nullable', 'string', 'max:120'],
            'cPassword' => [$isEdit ? 'nullable' : 'required', 'string', 'min:8'],
        ];
        $this->validate($rules);

        $payload = [
            'name' => $this->cName,
            'email' => $this->cEmail,
            'phone' => $this->cPhone ?: null,
            'city' => $this->cCity ?: null,
        ];
        if ($this->cPassword !== '') {
            $payload['password'] = Hash::make($this->cPassword);
        }

        if ($isEdit) {
            $client = User::role(Role::Client->value)->findOrFail($this->formId);
            $client->update($payload);
            unset($this->selected);
            $this->dispatch('tk-toast', type: 'success', message: 'Client mis à jour.');
        } else {
            $payload['status'] = 'active';
            $client = User::create($payload);
            $client->assignRole(Role::Client->value);
            $this->dispatch('tk-toast', type: 'success', message: 'Client créé.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->reset(['showForm', 'formId', 'cName', 'cEmail', 'cPhone', 'cCity', 'cPassword']);
        $this->resetErrorBag();
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = User::role(Role::Client->value)->withCount('serviceRequests')->latest();

        if ($this->search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
                ->orWhere('phone', 'like', '%'.$this->search.'%'));
        }

        return ['clients' => $query->paginate(20)];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Clients</h1>
        </div>
        @can('clients.manage')
            <x-tk.button wire:click="openCreate" variant="primary" icon="user-plus">Nouveau client</x-tk.button>
        @endcan
    </div>

    <div class="mt-6"><x-tk.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Nom, email ou téléphone…" /></div>

    <div class="mt-6">
        @forelse ($clients as $client)
            <button wire:key="c-{{ $client->id }}" wire:click="open({{ $client->id }})"
                    class="flex w-full items-center justify-between gap-4 border-b border-line py-3.5 text-left transition-colors hover:bg-tk-50">
                <div class="flex items-center gap-3">
                    <x-tk.avatar :name="$client->name" size="sm" />
                    <div>
                        <p class="font-medium text-content">{{ $client->name }}</p>
                        <p class="text-xs text-muted">{{ $client->email ?? $client->phone ?? '—' }}{{ $client->city ? ' · '.$client->city : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($client->isSuspended())<x-tk.badge tone="danger" dot>Suspendu</x-tk.badge>@endif
                    <x-tk.badge tone="neutral">{{ $client->service_requests_count }} demandes</x-tk.badge>
                    <x-lucide-chevron-right class="h-4 w-4 text-tk-300" />
                </div>
            </button>
        @empty
            <x-tk.empty-state icon="users" title="Aucun client">Les clients inscrits apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($clients->hasPages())<div class="mt-6">{{ $clients->links() }}</div>@endif

    {{-- Fiche client (drawer) --}}
    @if ($this->selected)
        @php($client = $this->selected)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/40" @keydown.escape.window="$wire.close()">
            <div @click.outside="$wire.close()" class="h-full w-full max-w-md overflow-y-auto bg-surface p-6 shadow-lift">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <x-tk.avatar :name="$client->name" />
                        <div>
                            <p class="font-display text-lg font-bold text-content">{{ $client->name }}</p>
                            <p class="text-sm text-muted">{{ $client->email ?? '—' }}</p>
                        </div>
                    </div>
                    <button wire:click="close" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($client->isSuspended())<x-tk.badge tone="danger" dot>Suspendu</x-tk.badge>@else<x-tk.badge tone="success" dot>Actif</x-tk.badge>@endif
                    <x-tk.badge tone="neutral">{{ $client->service_requests_count }} demandes</x-tk.badge>
                    <x-tk.badge tone="neutral">{{ $client->reviews_count }} avis</x-tk.badge>
                </div>

                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Téléphone</dt><dd class="font-medium text-content">{{ $client->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Ville</dt><dd class="font-medium text-content">{{ $client->city ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Inscrit le</dt><dd class="font-medium text-content">{{ $client->created_at->format('d/m/Y') }}</dd></div>
                </dl>

                {{-- Dernières demandes --}}
                <p class="mt-6 text-xs font-semibold uppercase tracking-wider text-tk-400">Dernières demandes</p>
                <div class="mt-2 space-y-1.5">
                    @forelse ($client->serviceRequests as $request)
                        <a href="{{ route('admin.service-requests.show', $request->reference) }}" wire:navigate class="flex items-center justify-between rounded-tile px-2 py-1.5 text-sm hover:bg-tk-50">
                            <span class="font-mono text-content">{{ $request->reference }}</span>
                            <x-tk.badge :tone="$request->status->tone()" dot>{{ $request->status->label() }}</x-tk.badge>
                        </a>
                    @empty
                        <p class="text-sm text-muted">Aucune demande.</p>
                    @endforelse
                </div>

                @can('clients.manage')
                    {{-- Notes internes --}}
                    <div class="mt-6">
                        <label class="text-xs font-semibold uppercase tracking-wider text-tk-400">Notes internes</label>
                        <textarea wire:model="notes" rows="3" class="mt-2 w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-sm text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                        <x-tk.button wire:click="saveNotes" variant="secondary" size="sm" class="mt-2">Enregistrer les notes</x-tk.button>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex flex-wrap gap-2 border-t border-line pt-4">
                        <x-tk.button wire:click="openEdit" variant="secondary" size="sm" icon="pencil">Modifier</x-tk.button>
                        <x-tk.button wire:click="toggleSuspend" variant="secondary" size="sm" icon="{{ $client->isSuspended() ? 'circle-check' : 'ban' }}">{{ $client->isSuspended() ? 'Réactiver' : 'Suspendre' }}</x-tk.button>
                        <x-tk.button wire:click="deleteClient" wire:confirm="Supprimer ce client ?" variant="danger" size="sm" icon="trash-2">Supprimer</x-tk.button>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    {{-- Modal créer / modifier --}}
    @if ($showForm)
        <div class="fixed inset-0 z-[60] flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.closeForm()">
            <div @click.outside="$wire.closeForm()" class="w-full max-w-md rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $formId ? 'Modifier le client' : 'Nouveau client' }}</h2>
                    <button @click="$wire.closeForm()" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="saveClient" class="mt-5 space-y-4">
                    <x-tk.field label="Nom" :error="$errors->first('cName')"><x-tk.input wire:model="cName" icon="user" /></x-tk.field>
                    <x-tk.field label="Email" :error="$errors->first('cEmail')"><x-tk.input wire:model="cEmail" type="email" icon="mail" /></x-tk.field>
                    <div class="grid grid-cols-2 gap-3">
                        <x-tk.field label="Téléphone" :error="$errors->first('cPhone')"><x-tk.input wire:model="cPhone" type="tel" icon="phone" /></x-tk.field>
                        <x-tk.field label="Ville" :error="$errors->first('cCity')"><x-tk.input wire:model="cCity" icon="map-pin" /></x-tk.field>
                    </div>
                    <x-tk.field label="Mot de passe" :error="$errors->first('cPassword')"
                                hint="{{ $formId ? 'Laisser vide pour ne pas changer.' : '8 caractères minimum — à communiquer au client.' }}">
                        <x-tk.input wire:model="cPassword" type="text" icon="key" />
                    </x-tk.field>
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary" icon="check"><span wire:loading.remove wire:target="saveClient">{{ $formId ? 'Enregistrer' : 'Créer le client' }}</span><span wire:loading wire:target="saveClient">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.closeForm()">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
