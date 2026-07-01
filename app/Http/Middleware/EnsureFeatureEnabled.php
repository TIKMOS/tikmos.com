<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\FeatureSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde une route derrière un feature flag configurable (Réglages → Fonctionnalités).
 * Usage : ->middleware('feature:blog')
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $settings = app(FeatureSettings::class);

        abort_unless(property_exists($settings, $feature) && $settings->{$feature} === true, 404);

        return $next($request);
    }
}
