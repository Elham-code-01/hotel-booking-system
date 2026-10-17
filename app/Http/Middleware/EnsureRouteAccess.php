<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the user's role against config/navigation.php.
 * Routes that are not listed there (for example profile.*) stay open to every active user.
 */
class EnsureRouteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        $item = collect(config('navigation.items'))
            ->first(fn ($item) => in_array($name, $item['routes'] ?? [$item['route']], true));

        if ($item && ! in_array($request->user()->role, $item['roles'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
