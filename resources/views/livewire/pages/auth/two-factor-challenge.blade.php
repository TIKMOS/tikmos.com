<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    public string $code = '';

    public bool $remember_device = false;

    /** Code affiché uniquement en développement local. */
    #[Locked]
    public ?string $devCode = null;

    #[Locked]
    public int $resendDelay = 0;

    #[Locked]
    public int $verifyDelay = 0;

    #[Locked]
    public string $status = '';

    public function boot(TwoFactorService $twoFactor): void
    {
        if (! $twoFactor->showsDevCode()) {
            $this->devCode = null;
            session()->forget('2fa.dev_code');
        }
    }

    public function mount(TwoFactorService $twoFactor): void
    {
        if (! session()->has('2fa.user_id')) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($twoFactor->showsDevCode()) {
            $this->devCode = session()->pull('2fa.dev_code');
        }
        if ($message = session()->pull('2fa.resend_error')) {
            $this->addError('resend', $message);
        }
        if ($user = $this->pendingUser()) {
            $this->refreshDelays($twoFactor, $user);
        }
    }

    public function verify(TwoFactorService $twoFactor): void
    {
        $this->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $this->pendingUser();

        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->status = '';
        try {
            $twoFactor->verify($user, $this->code);
        } catch (ValidationException $exception) {
            $this->refreshDelays($twoFactor, $user);
            $this->dispatch('otp-error', field: 'code');
            throw $exception;
        }

        Auth::login($user, (bool) session('2fa.remember', false));

        if ($this->remember_device) {
            $twoFactor->rememberDevice($user, request());
        }

        session()->forget(['2fa.user_id', '2fa.remember', '2fa.dev_code', '2fa.resend_error']);
        $this->devCode = null;
        $this->code = '';
        session()->regenerate();

        $this->redirectIntended(default: $user->homeRoute(), navigate: true);
    }

    public function resend(TwoFactorService $twoFactor): void
    {
        $user = $this->pendingUser();
        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }
        $this->status = '';
        try {
            $code = $twoFactor->sendCode($user);
        } catch (ValidationException $exception) {
            $this->refreshDelays($twoFactor, $user);
            $this->dispatch('otp-error', field: 'resend');
            throw $exception;
        }
        $this->devCode = $twoFactor->showsDevCode() ? $code : null;
        $this->code = '';
        $this->resetValidation();
        $this->status = 'Un nouveau code a été envoyé par email. Utilisez uniquement ce dernier code.';
        $this->refreshDelays($twoFactor, $user);
    }

    private function pendingUser(): ?User
    {
        $user = User::find(session('2fa.user_id'));

        return $user && $user->isActive() && $user->isStaff() ? $user : null;
    }

    private function refreshDelays(TwoFactorService $twoFactor, User $user): void
    {
        $delays = $twoFactor->delays($user);
        $this->resendDelay = $delays['resend'];
        $this->verifyDelay = $delays['verify'];
        // Les durées peuvent être identiques au précédent état serveur alors que le
        // compteur Alpine a atteint zéro : réinitialiser aussi par événement.
        $this->dispatch('otp-delays', resend: $this->resendDelay, verify: $this->verifyDelay);
    }
}; ?>

<div x-data="{
        resendRemaining: 0, verifyRemaining: 0, timer: null,
        init() { this.$el.classList.add('is-revealed'); this.timer = setInterval(() => { this.resendRemaining = Math.max(0, this.resendRemaining - 1); this.verifyRemaining = Math.max(0, this.verifyRemaining - 1); }, 1000); },
        destroy() { clearInterval(this.timer); }
    }"
    x-effect="resendRemaining = $wire.resendDelay; verifyRemaining = $wire.verifyDelay"
    x-on:otp-delays.window="resendRemaining = $event.detail.resend; verifyRemaining = $event.detail.verify"
    x-on:otp-error.window="$nextTick(() => document.getElementById('otp-' + $event.detail.field + '-error')?.focus())"
    class="reveal">
    <span class="mb-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-lucide-smartphone class="h-6 w-6" /></span>
    <h2 class="font-display text-3xl font-extrabold text-content">Vérification</h2>
    <p class="mt-1.5 text-sm text-muted">Saisissez le dernier code à 6 chiffres reçu par email. Il est valable {{ (int) ceil(app(TwoFactorService::class)->ttl() / 60) }} minutes.</p>

    @if ($devCode && app(TwoFactorService::class)->showsDevCode())
        <x-tk.dev-notice class="mt-5">
            Code de connexion : <button type="button" wire:click="$set('code', '{{ $devCode }}')" class="font-mono text-base font-bold underline decoration-dotted underline-offset-2">{{ $devCode }}</button>
            <span class="block text-xs opacity-80">Affiché car <code>APP_ENV=local</code>. Cliquez pour remplir.</span>
        </x-tk.dev-notice>
    @endif

    <form wire:submit="verify" class="mt-8 space-y-5">
        <x-tk.field label="Code de connexion" for="code">
            <input wire:model="code" id="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" autofocus placeholder="••••••" aria-describedby="otp-code-help{{ $errors->has('code') ? ' otp-code-error' : '' }}" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                   class="w-full rounded-input border bg-surface text-center font-mono text-2xl tracking-[0.5em] text-content placeholder:text-tk-300 min-h-[56px] px-4 transition-colors focus:outline-none focus:ring-4 focus:ring-ink/10 {{ $errors->has('code') ? 'border-danger' : 'border-tk-300 focus:border-ink' }}">
        </x-tk.field>
        <p id="otp-code-help" class="text-xs text-muted">Ne communiquez jamais ce code. Un renvoi remplace le code précédent.</p>
        @error('code')
            <p id="otp-code-error" role="alert" tabindex="-1" class="text-sm font-medium text-danger">{{ $message }}</p>
        @enderror
        <p x-cloak x-show="verifyRemaining > 0" class="text-sm text-muted" aria-live="off">Validation disponible dans <span x-text="verifyRemaining">{{ $verifyDelay }}</span> secondes.</p>

        <label class="flex min-h-11 items-center gap-2 text-sm text-content">
            <input type="checkbox" wire:model="remember_device" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20">
            Faire confiance à cet appareil pendant {{ (int) config('tikmos.two_factor.remember_days', 30) }} jours
        </label>

        <p class="text-xs text-muted">À cocher uniquement sur votre appareil personnel.</p>
        <x-tk.button type="submit" variant="primary" size="lg" icon="shield-check" class="w-full" x-bind:disabled="verifyRemaining > 0" wire:loading.attr="disabled" wire:target="verify,resend">
            <span wire:loading.remove wire:target="verify">Vérifier</span>
            <span wire:loading wire:target="verify">Vérification…</span>
        </x-tk.button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-4 text-sm">
        <button type="button" wire:click="resend" x-bind:disabled="resendRemaining > 0" wire:loading.attr="disabled" wire:target="resend,verify" aria-describedby="otp-resend-help{{ $errors->has('resend') ? ' otp-resend-error' : '' }}" class="inline-flex min-h-11 items-center gap-1 font-medium text-content underline-offset-2 hover:underline disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo"><x-lucide-rotate-cw class="h-4 w-4" /> Renvoyer le code</button>
        <a href="{{ route('login') }}" wire:navigate class="text-muted hover:text-content">Retour</a>
    </div>
    <p id="otp-resend-help" class="mt-2 text-xs text-muted" aria-live="off">
        <span x-show="resendRemaining > 0">Renvoi disponible dans <span x-text="resendRemaining">{{ $resendDelay }}</span> secondes.</span>
        <span x-cloak x-show="resendRemaining === 0">Vous pouvez demander un nouveau code.</span>
    </p>
    @error('resend')
        <p id="otp-resend-error" role="alert" tabindex="-1" class="mt-3 text-sm font-medium text-danger">{{ $message }}</p>
    @enderror
    <p role="status" class="mt-3 text-sm text-content">{{ $status }}</p>
</div>
