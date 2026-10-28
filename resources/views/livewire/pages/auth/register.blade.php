<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function next(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = 1;
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $validated['status'] = 'active';

        $user = User::create($validated);
        $user->assignRole(Role::Client->value);

        event(new Registered($user));
        Auth::login($user);
        session()->regenerate();

        $this->redirectIntended(default: '/app', navigate: true);
    }
}; ?>

<div x-data="{ previousStep: 1 }" x-effect="const step = $wire.step; if (step !== previousStep) { previousStep = step; $nextTick(() => (step === 1 ? $refs.registrationName : $refs.registrationEmail).focus()) }">
    {{-- Progression --}}
    <div class="mb-7">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-muted">
            <span>Étape {{ $step }} sur 2</span>
            <span>{{ $step === 1 ? 'Vos informations' : 'Sécurité' }}</span>
        </div>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-tk-200">
            <div class="h-full rounded-full bg-ink transition-all duration-500 ease-out" style="width: {{ $step === 1 ? '50%' : '100%' }}"></div>
        </div>
    </div>

    <h2 class="font-display text-3xl font-extrabold text-content">Créer un compte</h2>
    <p class="mt-1.5 text-sm text-muted">{{ $step === 1 ? 'Faisons connaissance.' : 'Sécurisez votre accès.' }}</p>

    {{-- Étape 1 --}}
    <div x-show="$wire.step === 1" x-transition.opacity.duration.300ms>
        <form wire:submit="next" class="mt-8 space-y-5">
            <x-tk.field label="Nom complet" for="name" :required="true" :error="$errors->first('name')">
                <x-tk.input x-ref="registrationName" wire:model="name" id="name" type="text" icon="user" placeholder="Votre nom" autocomplete="name" autofocus :invalid="$errors->has('name')" />
            </x-tk.field>

            <x-tk.field label="Téléphone" for="registration-phone" hint="Facilite le contact WhatsApp" :error="$errors->first('phone')">
                <x-tk.phone wire:model="phone" placeholder="Numéro de téléphone" :invalid="$errors->has('phone')" />
            </x-tk.field>

            <x-tk.button type="submit" variant="primary" size="lg" class="w-full" icon="arrow-right" :iconRight="true">Continuer</x-tk.button>
        </form>
    </div>

    {{-- Étape 2 --}}
    <div x-show="$wire.step === 2" x-cloak x-transition.opacity.duration.300ms>
        <form wire:submit="register" class="mt-8 space-y-5">
            <x-tk.field label="Email" for="email" :required="true" :error="$errors->first('email')">
                <x-tk.input x-ref="registrationEmail" wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" autocomplete="email" :invalid="$errors->has('email')" />
            </x-tk.field>

            <x-tk.field label="Mot de passe" for="password" :required="true" :error="$errors->first('password')">
                <x-tk.input wire:model="password" id="password" type="password" icon="lock-keyhole" placeholder="••••••••" autocomplete="new-password" :invalid="$errors->has('password')" />
            </x-tk.field>

            <x-tk.field label="Confirmer le mot de passe" for="password_confirmation" :required="true">
                <x-tk.input wire:model="password_confirmation" id="password_confirmation" type="password" icon="lock-keyhole" placeholder="••••••••" autocomplete="new-password" />
            </x-tk.field>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-tk.button type="button" wire:click="back" variant="secondary" size="lg" icon="arrow-left">Retour</x-tk.button>
                <x-tk.button type="submit" variant="primary" size="lg" class="flex-1">Créer mon compte</x-tk.button>
            </div>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-muted">
        Déjà inscrit ?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-content underline-offset-2 hover:underline">Se connecter</a>
    </p>
</div>
