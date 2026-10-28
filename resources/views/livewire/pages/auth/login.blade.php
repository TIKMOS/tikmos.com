<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(TwoFactorService $twoFactor): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey());
            event(new Failed('web', $user, ['email' => $this->email]));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Double authentification pour les rôles de gestion (sauf appareil de confiance).
        if ($twoFactor->isRequiredFor($user) && ! $twoFactor->isDeviceTrusted($user, request())) {
            session()->put('2fa.user_id', $user->id);
            session()->put('2fa.remember', $this->remember);

            session()->forget(['2fa.dev_code', '2fa.resend_error']);
            try {
                $code = $twoFactor->sendCode($user);
                if ($twoFactor->showsDevCode()) {
                    session()->flash('2fa.dev_code', $code);
                }
            } catch (ValidationException $exception) {
                // Un nouveau login avec le bon mot de passe conserve les quotas du compte.
                session()->flash('2fa.resend_error', $exception->errors()['resend'][0]);
            }

            $this->redirectRoute('two-factor.challenge', navigate: true);

            return;
        }

        Auth::login($user, $this->remember);
        session()->forget(['2fa.user_id', '2fa.remember', '2fa.dev_code', '2fa.resend_error']);
        session()->regenerate();

        $this->redirectIntended(default: $user->homeRoute(), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div x-data x-init="$el.classList.add('is-revealed')" class="reveal">
    <span class="mb-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-lucide-lock-keyhole class="h-6 w-6" /></span>
    <h2 class="font-display text-3xl font-extrabold text-content">Connexion</h2>
    <p class="mt-1.5 text-sm text-muted">Accédez à votre espace {{ config('app.name') }}.</p>

    @if (session('account_closed'))
        <div role="status" class="mt-5 rounded-tile border border-line bg-surface p-4 text-sm text-content">
            <p>Votre compte est fermé et vous êtes déconnecté. Vos données n’ont pas été automatiquement effacées.</p>
            <a href="{{ route('privacy') }}" wire:navigate class="mt-2 inline-block font-medium underline underline-offset-2">Comprendre la conservation de mes données</a>
        </div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
        <x-tk.field label="Email" for="email" :required="true" :error="$errors->first('email')">
            <x-tk.input wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" autocomplete="email" autofocus :invalid="$errors->has('email')" />
        </x-tk.field>

        <x-tk.field label="Mot de passe" for="password" :required="true" :error="$errors->first('password')">
            <x-tk.input wire:model="password" id="password" type="password" icon="lock-keyhole" placeholder="••••••••" autocomplete="current-password" :invalid="$errors->has('password')" />
        </x-tk.field>

        <div class="flex items-center justify-between">
            <label class="flex min-h-11 items-center gap-2 text-sm text-content">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-control-line text-ink focus:ring-ink/20">
                Se souvenir de moi
            </label>
            <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-content underline-offset-2 hover:underline">Mot de passe oublié ?</a>
        </div>

        <x-tk.button type="submit" variant="primary" size="lg" class="w-full">
            <span wire:loading.remove wire:target="login">Se connecter</span>
            <span wire:loading wire:target="login">Connexion…</span>
        </x-tk.button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Pas encore de compte ?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-content underline-offset-2 hover:underline">Créer un compte</a>
    </p>
</div>
