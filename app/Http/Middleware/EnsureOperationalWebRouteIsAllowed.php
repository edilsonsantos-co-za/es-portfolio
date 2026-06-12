<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperationalWebRouteIsAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'local') {
            return $next($request);
        }

        if (config('app.operational_web_routes', false) && $request->hasValidSignature()) {
            return $next($request);
        }

        abort(404);
    }
}
