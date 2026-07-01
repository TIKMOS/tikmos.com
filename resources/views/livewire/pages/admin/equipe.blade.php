<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public bool $showForm = false;

    public ?int $editId = null;

    public string $fName = '';

    public string $fEmail = '';

    public string $fPhone = '';

    public string $fRole = 'support';

    public string $fMode = 'email';   // 'email' = invitation ; 'password' = mot de passe défini

    public string $fPassword = '';

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);
    }

    /** @return array<int, Role> */
    public function manageableRoles(): array
    {
        return Role::management();
    }

    public function openCreate(): void
    {
        $this->authorizeManage();
        $this->reset(['editId', 'fName', 'fEmail', 'fPhone', 'fPassword']);
        $this->fRole = 'support';
        $this->fMode = 'email';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(User $user): void
    {
        $this->authorizeManage();
        $this->editId = $user->id;
        $this->fName = $user->name;
        $this->fEmail = (string) $user->email;
        $this->fPhone = (string) $user->phone;
        $this->fRole = $user->roles->first()?->name ?? 'support';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $isEdit = $this->editId !== null;
        $roleValues = array_map(fn (Role $r) => $r->value, Role::management());

        $rules = [
            'fName' => ['required', 'string', 'max:255'],
            'fEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editId)],
            'fPhone' => ['nullable', 'string', 'max:40'],
            'fRole' => ['required', Rule::in($roleValues)],
        ];
        if (! $isEdit && $this->fMode === 'password') {
            $rules['fPassword'] = ['required', 'string', 'min:8'];
        }
        $this->validate($rules);

        // Garde-fous super-administrateur.
        $superValue = Role::SuperAdmin->value;
        if ($this->fRole === $superValue && ! auth()->user()->hasRole($superValue)) {
            $this->dispatch('tk-toast', type: 'error', message: 'Le rôle super administrateur est réservé.');

            return;
        }

        if ($isEdit) {
            $user = User::findOrFail($this->editId);
            if ($user->isLastSuperAdmin() && $this->fRole !== $superValue) {
                $this->dispatch('tk-toast', type: 'error', message: 'Impossible : dernier super administrateur.');

                return;
            }
            $user->update(['name' => $this->fName, 'email' => $this->fEmail, 'phone' => $this->fPhone ?: null]);
            $user->syncRoles([$this->fRole]);
            $this->closeForm();
            $this->dispatch('tk-toast', type: 'success', message: 'Membre mis à jour.');

            return;
        }

        $user = User::create([
            'name' => $this->fName,
            'email' => $this->fEmail,
            'phone' => $this->fPhone ?: null,
            'password' => $this->fMode === 'password' ? Hash::make($this->fPassword) : Str::random(40),
            'status' => 'active',
            'invited_at' => $this->fMode === 'email' ? now() : null,
        ]);
        $user->assignRole($this->fRole);

        if ($this->fMode === 'email') {
            $ok = $this->sendInvite($user);
            $this->dispatch('tk-toast',
                type: $ok ? 'success' : 'error',
                message: $ok
                    ? 'Membre créé, invitation envoyée.'
                    : "Membre créé, mais l'email n'est pas parti (SMTP ?). Modifiez-le pour définir un mot de passe.",
            );
        } else {
            $this->dispatch('tk-toast', type: 'success', message: 'Membre créé avec mot de passe.');
        }
        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->reset(['showForm', 'editId', 'fName', 'fEmail', 'fPhone', 'fPassword']);
        $this->fRole = 'support';
        $this->fMode = 'email';
    }

    /** Envoie l'invitation en tolérant une panne SMTP. */
    protected function sendInvite(User $user): bool
    {
        try {
            $token = Password::broker()->createToken($user);
            $role = $user->roles->first()?->name;
            $user->notify(new StaffInvitation($token, $role ? Role::from($role)->label() : 'membre'));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public function resendInvite(User $user): void
    {
        $this->authorizeManage();
        $ok = $this->sendInvite($user);
        $this->dispatch('tk-toast',
            type: $ok ? 'success' : 'error',
            message: $ok ? 'Invitation renvoyée.' : "L'email n'a pas pu partir (vérifiez le SMTP).",
        );
    }

    public function changeRole(User $user, string $role): void
    {
        $this->authorizeManage();
        if ($user->isLastSuperAdmin() && $role !== Role::SuperAdmin->value) {
            $this->dispatch('tk-toast', type: 'error', message: 'Impossible : dernier super administrateur.');

            return;
        }
        if ($role === Role::SuperAdmin->value && ! auth()->user()->hasRole(Role::SuperAdmin->value)) {
            $this->dispatch('tk-toast', type: 'error', message: 'Réservé au super administrateur.');

            return;
        }
        $user->syncRoles([$role]);
        $this->dispatch('tk-toast', type: 'success', message: 'Rôle mis à jour.');
    }

    public function toggleSuspend(User $user): void
    {
        $this->authorizeManage();
        if ($user->id === auth()->id()) {
            $this->dispatch('tk-toast', type: 'error', message: 'Vous ne pouvez pas vous suspendre vous-même.');

            return;
        }
        if (! $user->isSuspended() && $user->isLastSuperAdmin()) {
            $this->dispatch('tk-toast', type: 'error', message: 'Impossible : dernier super administrateur.');

            return;
        }
        $user->isSuspended() ? $user->reactivate() : $user->suspend();
        $this->dispatch('tk-toast', type: 'success', message: $user->isSuspended() ? 'Compte suspendu.' : 'Compte réactivé.');
    }

    public function resetTwoFactor(User $user): void
    {
        $this->authorizeManage();
        $user->resetTwoFactor();
        $this->dispatch('tk-toast', type: 'success', message: '2FA réinitialisée (appareils révoqués).');
    }

    public function deleteMember(User $user): void
    {
        $this->authorizeManage();
        if ($user->id === auth()->id() || $user->isLastSuperAdmin()) {
            $this->dispatch('tk-toast', type: 'error', message: 'Action impossible sur ce compte.');

            return;
        }
        $user->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Membre retiré.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'members' => User::role(array_map(fn (Role $r) => $r->value, Role::management()))
                ->with('roles')->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Personnel</h1>
            <p class="mt-1 text-muted">Invitez et gérez l'équipe de gestion.</p>
        </div>
        @can('users.manage')
            <x-tk.button wire:click="openCreate" variant="primary" icon="user-plus">Ajouter un membre</x-tk.button>
        @endcan
    </div>

    <div class="mt-8 space-y-3">
        @forelse ($members as $member)
            <div wire:key="m-{{ $member->id }}" class="rounded-card border border-line bg-surface p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <x-tk.avatar :name="$member->name" size="sm" />
                        <div>
                            <p class="font-medium text-content">{{ $member->name }}
                                @if ($member->id === auth()->id())<span class="text-xs text-muted">(vous)</span>@endif
                            </p>
                            <p class="text-xs text-muted">{{ $member->email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($member->isSuspended())<x-tk.badge tone="danger" dot>Suspendu</x-tk.badge>@endif
                        @if ($member->invited_at && ! $member->two_factor_confirmed_at && $member->isActive())<x-tk.badge tone="warning">Invité</x-tk.badge>@endif
                        @foreach ($member->roles as $role)<x-tk.badge tone="neutral">{{ \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name }}</x-tk.badge>@endforeach
                    </div>
                </div>

                @can('users.manage')
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                        <select wire:change="changeRole({{ $member->id }}, $event.target.value)" class="min-h-[40px] rounded-input border border-tk-300 bg-surface px-3 text-sm text-content focus:border-ink focus:outline-none">
                            @foreach ($this->manageableRoles() as $r)
                                <option value="{{ $r->value }}" @selected($member->hasRole($r->value))>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                        <x-tk.button wire:click="openEdit({{ $member->id }})" variant="secondary" size="sm" icon="pencil">Modifier</x-tk.button>
                        <x-tk.button wire:click="toggleSuspend({{ $member->id }})" variant="secondary" size="sm" icon="{{ $member->isSuspended() ? 'circle-check' : 'ban' }}">{{ $member->isSuspended() ? 'Réactiver' : 'Suspendre' }}</x-tk.button>
                        <x-tk.button wire:click="resetTwoFactor({{ $member->id }})" variant="ghost" size="sm" icon="shield-alert">Reset 2FA</x-tk.button>
                        @if ($member->invited_at && ! $member->two_factor_confirmed_at)
                            <x-tk.button wire:click="resendInvite({{ $member->id }})" variant="ghost" size="sm" icon="send">Renvoyer</x-tk.button>
                        @endif
                        <button wire:click="deleteMember({{ $member->id }})" wire:confirm="Retirer ce membre ?" class="ml-auto grid h-9 w-9 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endcan
            </div>
        @empty
            <x-tk.empty-state icon="users" title="Aucun membre">Invitez votre premier collaborateur.</x-tk.empty-state>
        @endforelse
    </div>

    {{-- Modal créer / modifier --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.closeForm()">
            <div @click.outside="$wire.closeForm()" class="w-full max-w-md rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editId ? 'Modifier le membre' : 'Ajouter un membre' }}</h2>
                    <button @click="$wire.closeForm()" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Nom" :error="$errors->first('fName')"><x-tk.input wire:model="fName" icon="user" /></x-tk.field>
                    <x-tk.field label="Email" :error="$errors->first('fEmail')"><x-tk.input wire:model="fEmail" type="email" icon="mail" /></x-tk.field>
                    <x-tk.field label="Téléphone (optionnel)" :error="$errors->first('fPhone')"><x-tk.input wire:model="fPhone" type="tel" icon="phone" /></x-tk.field>
                    <x-tk.field label="Rôle" :error="$errors->first('fRole')">
                        <select wire:model="fRole" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none">
                            @foreach ($this->manageableRoles() as $r)<option value="{{ $r->value }}">{{ $r->label() }}</option>@endforeach
                        </select>
                    </x-tk.field>

                    @unless ($editId)
                        <x-tk.field label="Accès">
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex cursor-pointer items-center gap-2 rounded-input border px-3 py-2.5 text-sm {{ $fMode === 'email' ? 'border-ink bg-tk-50 font-medium text-content' : 'border-tk-300 text-muted' }}">
                                    <input type="radio" wire:model.live="fMode" value="email" class="accent-ink"> Invitation email
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-input border px-3 py-2.5 text-sm {{ $fMode === 'password' ? 'border-ink bg-tk-50 font-medium text-content' : 'border-tk-300 text-muted' }}">
                                    <input type="radio" wire:model.live="fMode" value="password" class="accent-ink"> Mot de passe
                                </label>
                            </div>
                        </x-tk.field>
                        @if ($fMode === 'password')
                            <x-tk.field label="Mot de passe" :error="$errors->first('fPassword')" hint="8 caractères minimum — à communiquer au membre.">
                                <x-tk.input wire:model="fPassword" type="text" icon="key" />
                            </x-tk.field>
                        @else
                            <p class="rounded-input bg-tk-50 px-3 py-2 text-xs text-muted">Un email d'invitation sera envoyé pour définir le mot de passe. Nécessite un SMTP configuré.</p>
                        @endif
                    @endunless

                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary" icon="check"><span wire:loading.remove wire:target="save">{{ $editId ? 'Enregistrer' : 'Créer le membre' }}</span><span wire:loading wire:target="save">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.closeForm()">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
