<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\ShopSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(ShopSettings::class)->enabled, 404);

        return $next($request);
    }
}
