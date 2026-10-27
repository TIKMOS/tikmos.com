<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PublicFormSubmissionLimiter
{
    public const LOCK_KEY = 'public-forms:v1:reservation';

    /** Réserve une tentative valide, avant tout enregistrement ou notification. */
    public function ensureAllowed(Request $request, string $email, string $phone): void
    {
        try {
            $storeName = (string) (config('cache.limiter') ?? config('cache.default'));
            $driver = config('cache.stores.'.$storeName.'.driver');
            if (! in_array($driver, ['database', 'file', 'redis', 'memcached', 'dynamodb'], true)
                && ! ($driver === 'array' && app()->environment('testing'))) {
                throw new RuntimeException('Cache persistant avec verrous requis pour les formulaires publics.');
            }
            $cache = Cache::store($storeName);
            $store = $cache->getStore();
            if (! $store instanceof LockProvider || (string) config('app.key') === '') {
                throw new RuntimeException('Configuration de protection des formulaires indisponible.');
            }
            $limiter = new RateLimiter($cache);
            $limits = $this->limits($request, $email, $phone);
            // Section courte : compteurs uniquement, sans création métier ni email sous verrou.
            $reserved = $store->lock(self::LOCK_KEY, 10)->get(function () use ($limiter, $limits): bool {
                $retry = 0;
                foreach ($limits as $limit) {
                    if ($limiter->tooManyAttempts($limit['key'], $limit['maximum'])) {
                        $retry = max($retry, $limiter->availableIn($limit['key']));
                    }
                }
                if ($retry > 0) {
                    $delay = $retry.($retry === 1 ? ' seconde.' : ' secondes.');
                    throw ValidationException::withMessages(['submission' => 'Plusieurs envois ont été effectués récemment. Réessayez dans '.$delay.' Votre texte est conservé.',
                    ]);
                }
                foreach ($limits as $limit) {
                    $limiter->hit($limit['key'], $limit['seconds']);
                }

                return true;
            });
            if ($reserved === false) {
                throw ValidationException::withMessages(['submission' => 'Un autre envoi est en cours. Réessayez dans 2 secondes. Votre texte est conservé.',
                ]);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Ne pas recopier une exception de connexion, les coordonnées ou les clés de cache.
            Log::warning('public_form_limiter_unavailable', ['exception_type' => $exception::class]);
            throw ValidationException::withMessages(['submission' => 'L’envoi est momentanément indisponible. Réessayez dans 30 secondes. Votre texte est conservé.',
            ]);
        }
    }

    /** @return list<array{key: string, maximum: int, seconds: int}> */
    private function limits(Request $request, string $email, string $phone): array
    {
        $actor = Auth::check() ? 'account:'.Auth::id() : 'session:'.Session::getId();
        $ip = $request->ip() ?? 'unknown';
        $packed = @inet_pton($ip);
        $limits = [
            ['key' => $this->key('actor', $actor), 'maximum' => 3, 'seconds' => 600],
            ['key' => $this->key('network', $packed === false ? $ip : bin2hex($packed)), 'maximum' => 20, 'seconds' => 600],
        ];
        if (trim($email) !== '') {
            $limits[] = ['key' => $this->key('email', mb_strtolower(trim($email))), 'maximum' => 5, 'seconds' => 1800];
        }
        if (trim($phone) !== '') {
            $digits = (string) preg_replace('/\D+/', '', $phone);
            $limits[] = ['key' => $this->key('phone', $digits !== '' ? $digits : mb_strtolower(trim($phone))), 'maximum' => 5, 'seconds' => 1800];
        }

        return $limits;
    }

    private function key(string $type, string $value): string
    {
        return 'public-forms:v1:'.$type.':'.hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
