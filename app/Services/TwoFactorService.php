<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Closure;
use Illuminate\Cache\Lock;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** @phpstan-type OtpState array{hash: string, expires: int, next_send: int, sends: int, send_until: int, failures: int, failure_until: int} */
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
        return max(1, (int) config('tikmos.two_factor.ttl', 600));
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('tikmos.two_factor.max_attempts', 3));
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
        return $this->locked($user, 'resend', function (Repository $cache, Lock $lock) use ($user): string {
            $state = $this->state($cache, $user);
            $delays = $this->delaysFor($state);
            if ($delays['resend'] > 0) {
                throw ValidationException::withMessages(['resend' => "Renvoi temporairement indisponible. Réessayez dans {$delays['resend']} secondes."]);
            }
            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (hash_equals($state['hash'], $this->hashCode($code)));

            if ($state['sends'] === 0) {
                $state['send_until'] = now()->timestamp + max(1, (int) config('tikmos.two_factor.send_window', 900));
            }
            $state['sends']++;
            $state['next_send'] = now()->timestamp + max(1, (int) config('tikmos.two_factor.resend_delay', 60));
            // Réserver le quota avant le transport ; une panne ne permet pas de spammer le fournisseur.
            // L'ancien code reste valable si le transport lève une exception.
            $this->save($cache, $user, $state);
            $user->notify(new TwoFactorCodeNotification($code, (int) ceil($this->ttl() / 60)));
            if (! $lock->isOwnedByCurrentProcess()) {
                // Ne pas écraser un code ou des quotas plus récents après un transport trop lent.
                throw new RuntimeException('Bail OTP perdu pendant le transport.');
            }
            $state['hash'] = $this->hashCode($code);
            $state['expires'] = now()->timestamp + $this->ttl();
            $this->save($cache, $user, $state);

            return $code;
        });
    }

    public function verify(User $user, #[\SensitiveParameter] string $code): bool
    {
        return $this->locked($user, 'code', function (Repository $cache) use ($user, $code): bool {
            $state = $this->state($cache, $user);
            $delay = $this->delaysFor($state)['verify'];
            if ($delay > 0) {
                throw ValidationException::withMessages(['code' => "Validation temporairement bloquée. Réessayez dans {$delay} secondes."]);
            }
            if ($state['hash'] === '' || $state['expires'] <= now()->timestamp) {
                throw ValidationException::withMessages(['code' => 'Ce code a expiré ou a déjà été utilisé. Demandez un nouveau code.']);
            }
            if (! hash_equals($state['hash'], $this->hashCode(trim($code)))) {
                if ($state['failures'] === 0) {
                    $state['failure_until'] = now()->timestamp + max(1, (int) config('tikmos.two_factor.attempt_window', 900));
                }
                $state['failures']++;
                $this->save($cache, $user, $state);
                $delay = $this->delaysFor($state)['verify'];
                $remaining = max(0, $this->maxAttempts() - $state['failures']);
                throw ValidationException::withMessages(['code' => $delay > 0
                    ? "Validation temporairement bloquée. Réessayez dans {$delay} secondes."
                    : "Code incorrect. Il vous reste {$remaining} tentative(s)."]);
            }
            // Consommation unique, sans effacer les quotas communs aux sessions et aux renvois.
            $state['hash'] = '';
            $state['expires'] = 0;
            $this->save($cache, $user, $state);

            return true;
        });
    }

    /** @return array{resend: int, verify: int} */
    public function delays(User $user): array
    {
        try {
            return $this->delaysFor($this->state($this->cache(), $user));
        } catch (Throwable $exception) {
            Log::warning('two_factor_unavailable', ['exception_type' => $exception::class]);

            return ['resend' => 30, 'verify' => 30];
        }
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

    private function cache(): Repository
    {
        $name = (string) (config('cache.limiter') ?? config('cache.default'));
        $driver = config('cache.stores.'.$name.'.driver');
        if ((! in_array($driver, ['database', 'file', 'redis', 'memcached', 'dynamodb'], true)
            && ! ($driver === 'array' && app()->environment('testing')))
            || (string) config('app.key') === '') {
            throw new RuntimeException('Cache OTP persistant avec verrous requis.');
        }
        $cache = Cache::store($name);
        if (! $cache instanceof Repository || ! $cache->getStore() instanceof LockProvider) {
            throw new RuntimeException('Verrous OTP indisponibles.');
        }

        return $cache;
    }

    /** @template T
     * @param  Closure(Repository, Lock): T  $action
     * @return T
     */
    private function locked(User $user, string $field, Closure $action): mixed
    {
        try {
            $cache = $this->cache();
            $store = $cache->getStore();
            if (! $store instanceof LockProvider) {
                throw new RuntimeException('Verrous OTP indisponibles.');
            }

            // Le transport mail doit avoir un timeout inférieur à ce bail (voir déploiement).
            $lock = $store->lock($this->stateKey($user).':lock', 60);
            if (! $lock instanceof Lock) {
                throw new RuntimeException('Verrou OTP incompatible.');
            }

            return $lock->get(fn () => $action($cache, $lock))
                ?: throw ValidationException::withMessages([$field => 'Une vérification est en cours. Réessayez dans 2 secondes.']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('two_factor_unavailable', ['exception_type' => $exception::class]);
            throw ValidationException::withMessages([$field => 'La vérification est momentanément indisponible. Réessayez dans 30 secondes.']);
        }
    }

    /** @return OtpState */
    private function state(Repository $cache, User $user): array
    {
        /** @var OtpState $state */
        $state = $cache->get($this->stateKey($user), [
            'hash' => '', 'expires' => 0, 'next_send' => 0, 'sends' => 0,
            'send_until' => 0, 'failures' => 0, 'failure_until' => 0,
        ]);
        if ($state['send_until'] <= now()->timestamp) {
            $state['sends'] = 0;
        }
        if ($state['failure_until'] <= now()->timestamp) {
            $state['failures'] = 0;
        }

        return $state;
    }

    /** @param OtpState $state
     * @return array{resend: int, verify: int}
     */
    private function delaysFor(array $state): array
    {
        $blocked = $state['failures'] >= $this->maxAttempts() ? max(0, $state['failure_until'] - now()->timestamp) : 0;
        $sendLimit = $state['sends'] >= max(1, (int) config('tikmos.two_factor.max_sends', 5)) ? $state['send_until'] : 0;

        return ['resend' => max(0, $state['next_send'] - now()->timestamp, $sendLimit - now()->timestamp, $blocked), 'verify' => $blocked];
    }

    /** @param OtpState $state */
    private function save(Repository $cache, User $user, array $state): void
    {
        $ttl = max($state['expires'], $state['next_send'], $state['send_until'], $state['failure_until']) - now()->timestamp;
        if (! $cache->put($this->stateKey($user), $state, max(1, $ttl))) {
            throw new RuntimeException('Écriture du cache OTP impossible.');
        }
    }

    private function stateKey(User $user): string
    {
        return '2fa:v2:'.hash_hmac('sha256', (string) $user->id, (string) config('app.key'));
    }

    private function hashCode(#[\SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function cookieName(User $user): string
    {
        return 'sz_device_'.hash('sha256', (string) $user->id);
    }
}
