<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $code = '';

    public bool $remember_device = true;

    /** Code affiché uniquement en développement local. */
    public ?string $devCode = null;

    public function mount(): void
    {
        if (! session()->has('2fa.user_id')) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->devCode = session('2fa.dev_code');
    }

    public function verify(TwoFactorService $twoFactor): void
    {
        $this->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = User::find(session('2fa.user_id'));

        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if (! $twoFactor->verify($user, $this->code)) {
            $remaining = $twoFactor->remainingAttempts($user);

            $this->addError('code', $remaining > 0
                ? "Code invalide. Il vous reste {$remaining} tentative(s)."
                : 'Trop de tentatives. Demandez un nouveau code.');

            return;
        }

        Auth::login($user, (bool) session('2fa.remember', false));

        if ($this->remember_device) {
            $twoFactor->rememberDevice($user, request());
        }

        session()->forget(['2fa.user_id', '2fa.remember']);
        session()->regenerate();

        $this->redirectIntended(default: $user->homeRoute(), navigate: true);
    }

    public function resend(TwoFactorService $twoFactor): void
    {
        $user = User::find(session('2fa.user_id'));

        if ($user) {
            $code = $twoFactor->sendCode($user);

            if ($twoFactor->showsDevCode()) {
                $this->devCode = $code;
            }

            $this->dispatch('sz-toast', type: 'success', message: 'Un nouveau code vous a été envoyé.');
        }
    }
}; ?>

<div x-data x-init="$el.classList.add('is-revealed')" class="reveal">
    <span class="mb-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-lucide-smartphone class="h-6 w-6" /></span>
    <h2 class="font-display text-3xl font-extrabold text-content">Vérification</h2>
    <p class="mt-1.5 text-sm text-muted">Un code à 6 chiffres vient de vous être envoyé par email.</p>

    @if ($devCode)
        <x-tk.dev-notice class="mt-5">
            Code de connexion : <button type="button" wire:click="$set('code', '{{ $devCode }}')" class="font-mono text-base font-bold underline decoration-dotted underline-offset-2">{{ $devCode }}</button>
            <span class="block text-xs opacity-80">Affiché car <code>APP_ENV=local</code>. Cliquez pour remplir.</span>
        </x-tk.dev-notice>
    @endif

    <form wire:submit="verify" class="mt-8 space-y-5">
        <x-tk.field label="Code de connexion" for="code" :error="$errors->first('code')">
            <input wire:model="code" id="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" autofocus placeholder="••••••"
                   class="w-full rounded-input border bg-surface text-center font-mono text-2xl tracking-[0.5em] text-content placeholder:text-tk-300 min-h-[56px] px-4 transition-colors focus:outline-none focus:ring-4 focus:ring-ink/10 {{ $errors->has('code') ? 'border-danger' : 'border-tk-300 focus:border-ink' }}">
        </x-tk.field>

        <label class="flex items-center gap-2 text-sm text-content">
            <input type="checkbox" wire:model="remember_device" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20">
            Faire confiance à cet appareil pendant 30 jours
        </label>

        <x-tk.button type="submit" variant="primary" size="lg" icon="shield-check" class="w-full">
            <span wire:loading.remove wire:target="verify">Vérifier</span>
            <span wire:loading wire:target="verify">Vérification…</span>
        </x-tk.button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <button type="button" wire:click="resend" wire:loading.attr="disabled" class="inline-flex items-center gap-1 font-medium text-content underline-offset-2 hover:underline"><x-lucide-rotate-cw class="h-4 w-4" /> Renvoyer le code</button>
        <a href="{{ route('login') }}" wire:navigate class="text-muted hover:text-content">Retour</a>
    </div>
</div>
