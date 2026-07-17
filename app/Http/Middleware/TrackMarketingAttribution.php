<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackMarketingAttribution
{
    /**
     * Query parameters captured into cookies for later server-side conversion
     * attribution (X/Twitter Ads click id + UTM campaign params).
     *
     * @var list<string>
     */
    private const TRACKED_PARAMS = [
        'twclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
    ];

    private const COOKIE_MINUTES = 60 * 24 * 30;

    /**
     * Capture ad click ids + UTM params from the landing URL into first-party
     * cookies so they survive until checkout and can be attached to the
     * server-side X Conversion API purchase event.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('stripe/*')) {
            return $next($request);
        }

        $response = $next($request);

        $captured = false;

        foreach (self::TRACKED_PARAMS as $param) {
            $value = $request->query($param);

            if (is_string($value) && $value !== '') {
                $response->headers->setCookie(cookie($param, $value, self::COOKIE_MINUTES));
                $captured = true;
            }
        }

        // Preserve the original landing URL alongside the first captured click,
        // but never overwrite an existing landing_url (keep the first touch).
        if ($captured && ! $request->cookie('landing_url')) {
            $response->headers->setCookie(cookie('landing_url', $request->fullUrl(), self::COOKIE_MINUTES));
        }

        return $response;
    }
}
