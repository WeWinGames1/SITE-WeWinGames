<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class EnsureSessionDomain
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only set session domain if it's not already set in config
        if (app()->environment('production') && !Config::get('session.domain')) {
            $host = $request->getHost();

            // Extract the base domain (e.g., wewingames.com from www.wewingames.com)
            if (strpos($host, 'wewingames.com') !== false) {
                // Only set if not already configured
                Config::set('session.domain', '.wewingames.com');
            }
        }

        return $next($request);
    }
}
