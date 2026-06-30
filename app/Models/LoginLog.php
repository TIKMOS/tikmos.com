<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'email', 'action', 'ip_address', 'user_agent', 'device_type', 'browser', 'os', 'logged_in_at'])]
class LoginLog extends Model
{
    protected function casts(): array
    {
        return ['logged_in_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Analyse légère du User-Agent (sans dépendance externe).
     *
     * @return array{device_type: string, browser: string, os: string}
     */
    public static function parseUserAgent(?string $ua): array
    {
        $ua ??= '';

        $device = preg_match('/Mobile|Android|iPhone|iPad/i', $ua) ? 'mobile' : 'desktop';

        $browser = match (true) {
            (bool) preg_match('/Edg/i', $ua) => 'Edge',
            (bool) preg_match('/OPR|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/Firefox/i', $ua) => 'Firefox',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => 'Inconnu',
        };

        $os = match (true) {
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iOS/i', $ua) => 'iOS',
            (bool) preg_match('/Mac OS/i', $ua) => 'macOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => 'Inconnu',
        };

        return ['device_type' => $device, 'browser' => $browser, 'os' => $os];
    }
}
