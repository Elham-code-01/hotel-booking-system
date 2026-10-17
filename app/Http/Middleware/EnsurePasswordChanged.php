<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** After an administrator reset, the only page allowed is "My profile" until a new password is chosen. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs('profile.edit', 'profile.password')) {
            return redirect()->route('profile.edit')->with('error', 'Please choose a new password to continue.');
        }

        return $next($request);
    }
}
