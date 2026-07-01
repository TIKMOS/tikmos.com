<?php

use App\Models\TeamMember;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $role_title = '';

    public string $bio = '';

    public array $socials = [];

    public array $funfacts = [];

    public bool $is_featured = false;

    public bool $is_active = true;

    public $photo = null;

    public ?string $currentPhoto = null;

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'role_title', 'bio', 'socials', 'funfacts', 'photo', 'currentPhoto']);
        $this->is_active = true;
        $this->is_featured = false;
        $this->showForm = true;
    }

    public function edit(TeamMember $member): void
    {
        $this->editingId = $member->id;
        $this->name = $member->name;
        $this->role_title = $member->role_title;
        $this->bio = (string) $member->bio;
        $this->socials = $member->socials ?? [];
        $this->funfacts = $member->funfacts ?? [];
        $this->is_featured = $member->is_featured;
        $this->is_active = $member->is_active;
        $this->currentPhoto = $member->photoUrl();
        $this->photo = null;
        $this->showForm = true;
    }

    public function addSocial(): void
    {
        $this->socials[] = ['network' => 'linkedin', 'url' => ''];
    }

    public function removeSocial(int $i): void
    {
        unset($this->socials[$i]);
        $this->socials = array_values($this->socials);
    }

    public function addFunfact(): void
    {
        $this->funfacts[] = '';
    }

    public function removeFunfact(int $i): void
    {
        unset($this->funfacts[$i]);
        $this->funfacts = array_values($this->funfacts);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'role_title' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:400'],
            'socials' => ['array'],
            'socials.*.network' => ['required', 'string', 'max:30'],
            'socials.*.url' => ['nullable', 'string', 'max:255'],
            'funfacts' => ['array'],
            'funfacts.*' => ['nullable', 'string', 'max:60'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $member = $this->editingId
            ? TeamMember::findOrFail($this->editingId)
            : new TeamMember(['position' => (int) TeamMember::max('position') + 1]);

        $member->fill([
            'name' => $data['name'],
            'role_title' => $data['role_title'],
            'bio' => $data['bio'] ?: null,
            'socials' => array_values(array_filter($this->socials, fn ($s) => trim((string) ($s['url'] ?? '')) !== '')),
            'funfacts' => array_values(array_filter($this->funfacts, fn ($f) => trim((string) $f) !== '')),
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
        ]);
        $member->save();

        if ($this->photo) {
            $member->addMedia($this->photo->getRealPath())
                ->usingFileName(\Illuminate\Support\Str::slug($this->name).'.'.$this->photo->getClientOriginalExtension())
                ->toMediaCollection('photo');
        }

        $this->showForm = false;
        $this->dispatch('tk-toast', type: 'success', message: $this->editingId ? 'Membre mis à jour.' : 'Membre ajouté.');
    }

    public function toggle(TeamMember $member): void
    {
        $member->update(['is_active' => ! $member->is_active]);
    }

    public function delete(TeamMember $member): void
    {
        $member->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Membre supprimé.');
    }

    /** @param  array<int, string>  $ids */
    public function reorder(array $ids): void
    {
        foreach ($ids as $position => $id) {
            TeamMember::whereKey($id)->update(['position' => $position]);
        }
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['members' => TeamMember::orderBy('position')->get()];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Équipe</h1>
            <p class="mt-1 text-muted">Membres affichés sur la page Équipe. Glissez pour réordonner.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Ajouter un membre</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($members->isEmpty())
            <x-tk.empty-state icon="users" title="Aucun membre">Présentez votre équipe.</x-tk.empty-state>
        @else
            <x-tk.sortable-list onReorder="reorder">
                @foreach ($members as $member)
                    <li wire:key="member-{{ $member->id }}" data-id="{{ $member->id }}" class="flex items-center gap-4 rounded-card border border-line bg-surface p-3 pr-4">
                        <x-lucide-grip-vertical class="h-5 w-5 shrink-0 cursor-grab text-tk-300" />
                        @if ($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="h-12 w-12 shrink-0 rounded-full object-cover">
                        @else
                            <x-tk.avatar :name="$member->name" size="sm" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $member->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $member->role_title }}</p>
                        </div>
                        <button wire:click="toggle({{ $member->id }})" class="shrink-0"><x-tk.badge :tone="$member->is_active ? 'success' : 'neutral'" dot>{{ $member->is_active ? 'Visible' : 'Masqué' }}</x-tk.badge></button>
                        <button wire:click="edit({{ $member->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $member->id }})" wire:confirm="Supprimer ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </li>
                @endforeach
            </x-tk.sortable-list>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier le membre' : 'Nouveau membre' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Nom" :error="$errors->first('name')"><x-tk.input wire:model="name" /></x-tk.field>
                        <x-tk.field label="Rôle / poste" :error="$errors->first('role_title')"><x-tk.input wire:model="role_title" placeholder="Fondateur" /></x-tk.field>
                    </div>
                    <x-tk.media-input model="photo" :preview="$currentPhoto" label="Photo" hint="Portrait — PNG/JPG/WebP, 2 Mo." />
                    <x-tk.field label="Bio courte" :error="$errors->first('bio')">
                        <textarea wire:model="bio" rows="2" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                    </x-tk.field>

                    <div>
                        <p class="mb-1.5 text-[13px] font-medium text-content">Réseaux</p>
                        <div class="space-y-2">
                            @foreach ($socials as $i => $social)
                                <div class="flex items-center gap-2" wire:key="tsocial-{{ $i }}">
                                    <select wire:model="socials.{{ $i }}.network" class="min-h-[48px] w-36 shrink-0 rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none">
                                        @foreach (['linkedin', 'instagram', 'twitter', 'facebook', 'globe'] as $net)<option value="{{ $net }}">{{ ucfirst($net) }}</option>@endforeach
                                    </select>
                                    <x-tk.input wire:model="socials.{{ $i }}.url" placeholder="https://…" class="flex-1" />
                                    <button type="button" wire:click="removeSocial({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addSocial" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un réseau</button>
                    </div>

                    <div>
                        <p class="mb-1.5 text-[13px] font-medium text-content">Fun facts</p>
                        <div class="space-y-2">
                            @foreach ($funfacts as $i => $fact)
                                <div class="flex items-center gap-2" wire:key="fact-{{ $i }}">
                                    <x-tk.input wire:model="funfacts.{{ $i }}" placeholder="Ex. 4 cafés par jour" class="flex-1" />
                                    <button type="button" wire:click="removeFunfact({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addFunfact" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un fun fact</button>
                    </div>

                    <div class="flex flex-wrap gap-6">
                        <x-tk.toggle label="Mis en avant" wire:model="is_featured" />
                        <x-tk.toggle label="Visible" wire:model="is_active" />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary"><span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
