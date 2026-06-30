<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user): void {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('toast', ['type' => 'success', 'message' => __($status)]);
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

<div x-data x-init="$el.classList.add('is-revealed')" class="reveal">
    <span class="mb-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-lucide-shield-check class="h-6 w-6" /></span>
    <h2 class="font-display text-3xl font-extrabold text-content">Nouveau mot de passe</h2>
    <p class="mt-1.5 text-sm text-muted">Choisissez un mot de passe sécurisé.</p>

    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <x-tk.field label="Email" for="email" :error="$errors->first('email')">
            <x-tk.input wire:model="email" id="email" type="email" icon="mail" autocomplete="email" :invalid="$errors->has('email')" />
        </x-tk.field>

        <x-tk.field label="Mot de passe" for="password" :error="$errors->first('password')">
            <x-tk.input wire:model="password" id="password" type="password" icon="lock-keyhole" placeholder="••••••••" autocomplete="new-password" :invalid="$errors->has('password')" />
        </x-tk.field>

        <x-tk.field label="Confirmer le mot de passe" for="password_confirmation">
            <x-tk.input wire:model="password_confirmation" id="password_confirmation" type="password" icon="lock-keyhole" placeholder="••••••••" autocomplete="new-password" />
        </x-tk.field>

        <x-tk.button type="submit" variant="primary" size="lg" icon="check" class="w-full">
            <span wire:loading.remove wire:target="resetPassword">Réinitialiser</span>
            <span wire:loading wire:target="resetPassword">Enregistrement…</span>
        </x-tk.button>
    </form>
</div>
