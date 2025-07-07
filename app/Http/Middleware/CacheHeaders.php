<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAge = '3600'): Response
    {
        $response = $next($request);

        // Only add cache headers for GET requests and successful responses
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
            $response->headers->set('X-Cache-Status', 'HIT');

            // Add ETag for better cache validation
            $etag = md5($response->getContent());
            $response->headers->set('ETag', $etag);

            // Check if client has the same ETag
            if ($request->headers->get('If-None-Match') === $etag) {
                $response->setStatusCode(304);
                $response->setContent('');
            }
        }

        return $response;
    }
}
