<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers to apply
     */
    private array $headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'X-Permitted-Cross-Domain-Policies' => 'none',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apply security headers
        foreach ($this->headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        // Content Security Policy
        $this->setContentSecurityPolicy($response);

        // Strict Transport Security (only for HTTPS)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Remove sensitive headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Set Content Security Policy header
     */
    private function setContentSecurityPolicy(Response $response): void
    {
        $csp = [
            "default-src 'self'",
        ];

        // Different CSP for development
        if (app()->environment('local')) {
            // More permissive in development
            $csp[] = "script-src * 'unsafe-inline' 'unsafe-eval'";
            $csp[] = "script-src-elem * 'unsafe-inline'";
            $csp[] = "style-src * 'unsafe-inline'";
            $csp[] = "style-src-elem * 'unsafe-inline'";
            $csp[] = 'font-src * data:';
            $csp[] = 'connect-src *';
        } else {
            $csp[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdnjs.cloudflare.com https://unpkg.com https://www.googletagmanager.com https://www.google-analytics.com https://challenges.cloudflare.com";
            $csp[] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://rsms.me";
            $csp[] = "connect-src 'self' https://api.stripe.com https://www.google-analytics.com https://region1.google-analytics.com wss://localhost:* ws://localhost:*";
        }

        // Common CSP directives - skip font-src if already set in local
        $commonCsp = [
            ! app()->environment('local') ? "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://rsms.me data:" : null,
            "img-src 'self' data: https: blob:",
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        // Filter out null values and merge
        $csp = array_merge($csp, array_filter($commonCsp));

        // Only add these in production
        if (! app()->environment('local')) {
            $csp[] = 'block-all-mixed-content';
            $csp[] = 'upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
    }
}
