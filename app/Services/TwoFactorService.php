<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class TwoFactorService
{
    public function enabled(): bool
    {
        return (bool) config('tikmos.two_factor.enabled', true);
    }

    public function isRequiredFor(User $user): bool
    {
        return $this->enabled() && $user->isStaff();
    }

    public function ttl(): int
    {
        return (int) config('tikmos.two_factor.ttl', 600);
    }

    public function maxAttempts(): int
    {
        return (int) config('tikmos.two_factor.max_attempts', 3);
    }

    /**
     * Le code OTP doit-il être affiché à l'écran ?
     * STRICTEMENT réservé au développement local (jamais en production).
     */
    public function showsDevCode(): bool
    {
        return app()->environment('local')
            && (bool) config('tikmos.two_factor.dev_show_code', true);
    }

    /** Génère, stocke (hashé) et envoie un code. Retourne le code (utile en dev). */
    public function sendCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->codeKey($user), hash('sha256', $code), $this->ttl());
        Cache::put($this->attemptsKey($user), 0, $this->ttl());

        $user->notify(new TwoFactorCodeNotification($code, (int) ceil($this->ttl() / 60)));

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        $stored = Cache::get($this->codeKey($user));

        if (! is_string($stored)) {
            return false;
        }

        if ((int) Cache::get($this->attemptsKey($user), 0) >= $this->maxAttempts()) {
            return false;
        }

        if (! hash_equals($stored, hash('sha256', trim($code)))) {
            Cache::increment($this->attemptsKey($user));

            return false;
        }

        Cache::forget($this->codeKey($user));
        Cache::forget($this->attemptsKey($user));

        return true;
    }

    public function remainingAttempts(User $user): int
    {
        return max(0, $this->maxAttempts() - (int) Cache::get($this->attemptsKey($user), 0));
    }

    public function isDeviceTrusted(User $user, Request $request): bool
    {
        $token = $request->cookie($this->cookieName($user));

        if (! is_string($token) || $token === '') {
            return false;
        }

        return TrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('token', hash('sha256', $token))
            ->valid()
            ->exists();
    }

    public function rememberDevice(User $user, Request $request): void
    {
        $token = Str::random(48);
        $days = (int) config('tikmos.two_factor.remember_days', 30);

        TrustedDevice::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays($days),
        ]);

        Cookie::queue($this->cookieName($user), $token, $days * 24 * 60);
    }

    private function codeKey(User $user): string
    {
        return "2fa:code:{$user->id}";
    }

    private function attemptsKey(User $user): string
    {
        return "2fa:attempts:{$user->id}";
    }

    private function cookieName(User $user): string
    {
        return 'sz_device_'.hash('sha256', (string) $user->id);
    }
}
