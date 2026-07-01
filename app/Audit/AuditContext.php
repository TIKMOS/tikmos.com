<?php

declare(strict_types=1);

namespace App\Audit;

use Illuminate\Http\Request;

/**
 * Capte le contexte d'une action (IP, user-agent, canal) pour l'attacher
 * à chaque activité journalisée. Défensif hors requête HTTP (console, queue).
 */
final class AuditContext
{
    /** @return array<string, string> */
    public static function capture(): array
    {
        if (app()->runningInConsole()) {
            return ['channel' => 'system'];
        }

        $request = request();

        return array_filter([
            'ip' => (string) $request->ip(),
            'ua' => mb_substr((string) $request->userAgent(), 0, 255),
            'channel' => self::channel($request),
        ]);
    }

    private static function channel(Request $request): string
    {
        return match (true) {
            $request->is('admin', 'admin/*') => 'admin',
            $request->is('app', 'app/*') => 'app',
            default => 'web',
        };
    }
}
