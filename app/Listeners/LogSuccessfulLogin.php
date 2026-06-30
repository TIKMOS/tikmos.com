<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $ua = request()->userAgent();
        $parsed = LoginLog::parseUserAgent($ua);

        LoginLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->getAttribute('email'),
            'action' => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => $ua,
            'device_type' => $parsed['device_type'],
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'logged_in_at' => now(),
        ]);
    }
}
