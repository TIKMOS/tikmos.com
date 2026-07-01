<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public function sendLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->reset('email');
            session()->flash('toast', ['type' => 'success', 'message' => __($status)]);
            $this->dispatch('tk-toast', type: 'success', message: __($status));

            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

<div x-data x-init="$el.classList.add('is-revealed')" class="reveal">
    <span class="mb-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-lucide-key-round class="h-6 w-6" /></span>
    <h2 class="font-display text-3xl font-extrabold text-content">Mot de passe oublié</h2>
    <p class="mt-1.5 text-sm text-muted">Indiquez votre email, nous vous enverrons un lien de réinitialisation.</p>

    <form wire:submit="sendLink" class="mt-8 space-y-5">
        <x-tk.field label="Email" for="email" :error="$errors->first('email')">
            <x-tk.input wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" autocomplete="email" autofocus :invalid="$errors->has('email')" />
        </x-tk.field>

        <x-tk.button type="submit" variant="primary" size="lg" icon="send" class="w-full">
            <span wire:loading.remove wire:target="sendLink">Envoyer le lien</span>
            <span wire:loading wire:target="sendLink">Envoi…</span>
        </x-tk.button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-1 font-medium text-content underline-offset-2 hover:underline"><x-lucide-arrow-left class="h-4 w-4" /> Retour à la connexion</a>
    </p>
</div>
