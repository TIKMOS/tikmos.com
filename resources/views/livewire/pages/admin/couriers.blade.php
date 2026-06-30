<?php

use App\Enums\CommissionType;
use App\Enums\Role;
use App\Models\Courier;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $contact_phone = '';

    public string $contact_email = '';

    public string $commission_type = 'per_delivery';

    public ?int $commission_value = 0;

    public bool $is_active = true;

    public $logo = null;

    public ?string $currentLogo = null;

    // Gestion livreur
    public ?int $livreurCourierId = null;

    public string $livreurName = '';

    public string $livreurEmail = '';

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'contact_phone', 'contact_email', 'logo', 'currentLogo']);
        $this->commission_type = 'per_delivery';
        $this->commission_value = 0;
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(Courier $courier): void
    {
        $this->editingId = $courier->id;
        $this->name = $courier->name;
        $this->contact_phone = (string) $courier->contact_phone;
        $this->contact_email = (string) $courier->contact_email;
        $this->commission_type = $courier->commission_type->value;
        $this->commission_value = $courier->commission_value;
        $this->is_active = $courier->is_active;
        $this->currentLogo = $courier->logoUrl();
        $this->logo = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'commission_type' => ['required', 'in:percent,flat,per_delivery,none'],
            'commission_value' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $courier = $this->editingId ? Courier::findOrFail($this->editingId) : new Courier();
        $courier->fill([
            'name' => $data['name'],
            'contact_phone' => $data['contact_phone'] ?: null,
            'contact_email' => $data['contact_email'] ?: null,
            'commission_type' => $data['commission_type'],
            'commission_value' => (int) $data['commission_value'],
            'is_active' => $this->is_active,
        ]);
        if (! $courier->exists || ! $courier->slug) {
            $courier->slug = $this->uniqueSlug($data['name']);
        }
        $courier->save();

        if ($this->logo) {
            $courier->addMedia($this->logo->getRealPath())->usingFileName(Str::slug($this->name).'.'.$this->logo->getClientOriginalExtension())->toMediaCollection('logo');
        }

        $this->showForm = false;
        $this->dispatch('sz-toast', type: 'success', message: 'Transporteur enregistré.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Courier::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function toggle(Courier $courier): void
    {
        $courier->update(['is_active' => ! $courier->is_active]);
    }

    public function inviteLivreur(): void
    {
        $data = $this->validate([
            'livreurCourierId' => ['required', 'exists:couriers,id'],
            'livreurName' => ['required', 'string', 'max:255'],
            'livreurEmail' => ['required', 'email', 'unique:users,email'],
        ], attributes: ['livreurName' => 'nom', 'livreurEmail' => 'email']);

        $user = User::create([
            'name' => $data['livreurName'],
            'email' => $data['livreurEmail'],
            'password' => Str::random(40),
            'status' => 'active',
            'invited_at' => now(),
            'courier_id' => $data['livreurCourierId'],
        ]);
        $user->assignRole(Role::Livreur->value);

        $token = Password::broker()->createToken($user);
        $user->notify(new StaffInvitation($token, Role::Livreur->label()));

        $this->reset(['livreurCourierId', 'livreurName', 'livreurEmail']);
        $this->dispatch('sz-toast', type: 'success', message: 'Livreur invité.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'couriers' => Courier::withCount(['livreurs', 'deliveries'])
                ->withSum('deliveries', 'commission_amount')
                ->orderBy('name')->get(),
            'commissionTypes' => CommissionType::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Logistique</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Transporteurs</h1>
            <p class="mt-1 text-muted">Sociétés de livraison, commissions et comptes livreurs.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Nouveau transporteur</x-tk.button>
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        @forelse ($couriers as $courier)
            <div wire:key="cr-{{ $courier->id }}" class="rounded-card border border-line bg-surface p-5">
                <div class="flex items-start gap-4">
                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-tile border border-line bg-canvas">
                        @if ($courier->logoUrl())<img src="{{ $courier->logoUrl() }}" alt="{{ $courier->name }}" class="max-h-full max-w-full object-contain p-1">@else<x-lucide-truck class="h-6 w-6 text-tk-300" />@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-display text-lg font-bold text-content">{{ $courier->name }}</p>
                            <button wire:click="toggle({{ $courier->id }})"><x-tk.badge :tone="$courier->is_active ? 'success' : 'neutral'" dot>{{ $courier->is_active ? 'Actif' : 'Inactif' }}</x-tk.badge></button>
                        </div>
                        <p class="text-xs text-muted">{{ $courier->commission_type->label() }}@if ($courier->commission_type->value !== 'none') : {{ $courier->commission_value }}{{ $courier->commission_type->value === 'percent' ? ' %' : ' FCFA' }} @endif</p>
                        <p class="mt-1 text-xs text-muted">{{ $courier->contact_phone }}</p>
                    </div>
                    <x-tk.button wire:click="edit({{ $courier->id }})" variant="ghost" size="sm" icon="pencil">Éditer</x-tk.button>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-line pt-4 text-center">
                    <div><p class="font-display text-xl font-bold text-content">{{ $courier->deliveries_count }}</p><p class="text-xs text-muted">livraisons</p></div>
                    <div><p class="font-display text-xl font-bold text-content">{{ $courier->livreurs_count }}</p><p class="text-xs text-muted">livreurs</p></div>
                    <div><p class="font-display text-xl font-bold text-content">{{ number_format((int) $courier->deliveries_sum_commission_amount, 0, ',', ' ') }}</p><p class="text-xs text-muted">FCFA commission</p></div>
                </div>

                <button wire:click="$set('livreurCourierId', {{ $courier->id }})" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-user-plus class="h-4 w-4" /> Inviter un livreur</button>
            </div>
        @empty
            <div class="lg:col-span-2"><x-tk.empty-state icon="truck" title="Aucun transporteur">Ajoutez vos sociétés de livraison partenaires.</x-tk.empty-state></div>
        @endforelse
    </div>

    {{-- Modal transporteur --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier' : 'Nouveau transporteur' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Nom de la société" :error="$errors->first('name')"><x-tk.input wire:model="name" placeholder="DDL Express" /></x-tk.field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Téléphone" :error="$errors->first('contact_phone')"><x-tk.input wire:model="contact_phone" /></x-tk.field>
                        <x-tk.field label="Email" :error="$errors->first('contact_email')"><x-tk.input wire:model="contact_email" type="email" /></x-tk.field>
                    </div>
                    <x-tk.media-input model="logo" :preview="$currentLogo" label="Logo" hint="PNG/JPG/WebP — 2 Mo." />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Type de commission">
                            <select wire:model="commission_type" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none">
                                @foreach ($commissionTypes as $ct)<option value="{{ $ct->value }}">{{ $ct->label() }}</option>@endforeach
                            </select>
                        </x-tk.field>
                        <x-tk.field label="Valeur" :error="$errors->first('commission_value')"><x-tk.input wire:model="commission_value" type="number" min="0" /></x-tk.field>
                    </div>
                    <x-tk.toggle label="Actif" wire:model="is_active" />
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary"><span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal invitation livreur --}}
    @if ($livreurCourierId)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('livreurCourierId', null)">
            <div @click.outside="$wire.set('livreurCourierId', null)" class="w-full max-w-md rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <h2 class="font-display text-xl font-bold text-content">Inviter un livreur</h2>
                <form wire:submit="inviteLivreur" class="mt-5 space-y-4">
                    <x-tk.field label="Nom" :error="$errors->first('livreurName')"><x-tk.input wire:model="livreurName" icon="user" /></x-tk.field>
                    <x-tk.field label="Email" :error="$errors->first('livreurEmail')"><x-tk.input wire:model="livreurEmail" type="email" icon="mail" /></x-tk.field>
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary" icon="send">Inviter</x-tk.button>
                        <x-tk.button type="button" variant="ghost" wire:click="$set('livreurCourierId', null)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
