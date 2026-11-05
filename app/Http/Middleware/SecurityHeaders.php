<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser security headers for the site and the admin panel.
 *
 * The CSP is deliberately limited to directives that cannot break Livewire,
 * Alpine or Filament (which rely on inline scripts and styles): it blocks
 * framing (clickjacking), <base> hijacking, plugins, and form posts to other
 * origins. Responses that already set a CSP (private files) keep theirs.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('X-Frame-Options', 'DENY', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()', false);
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin', false);

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self'");
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
