<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent caching of admin pages (important for Inertia.js with Cloudflare)
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Set security headers for admin routes
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Add Strict Transport Security in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Respect a CSP explicitly set by a controller (e.g. the raw HTML
        // preview endpoint, which needs the relaxed raw-page policy so the
        // previewed page can load its own third-party scripts/styles).
        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        // Content Security Policy for admin area
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://unpkg.com https://cdn.tiny.cloud https://www.googletagmanager.com https://www.google-analytics.com https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.bunny.net",
            "img-src 'self' data: https: blob:",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.bunny.net",
            "connect-src 'self' https://api.stripe.com https://www.google-analytics.com https://region1.google-analytics.com wss://localhost:* ws://localhost:*",
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        // Only add upgrade-insecure-requests in production
        if (app()->environment('production')) {
            $csp[] = 'upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
