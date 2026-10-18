<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Safe browser rules added to every page. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // The site can never be shown inside another site's frame (clickjacking).
        $response->headers->set('X-Frame-Options', 'DENY');
        // The browser must trust the type the server declares.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Other sites only learn that a visit came from here, never the page address.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // Financial pages are not kept by the browser, so the Back button after sign-out shows nothing.
        $response->headers->set('Cache-Control', 'no-store, private');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
