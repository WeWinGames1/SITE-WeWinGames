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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // For production, ensure session domain is set correctly
        if (app()->environment('production')) {
            $host = $request->getHost();
            
            // Extract the base domain (e.g., wewingames.com from www.wewingames.com)
            if (strpos($host, 'wewingames.com') !== false) {
                Config::set('session.domain', '.wewingames.com');
            }
        }
        
        return $next($request);
    }
}