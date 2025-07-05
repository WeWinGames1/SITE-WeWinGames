<?php

namespace App\Http\Middleware;

use App\Models\UnderConstructionSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class UnderConstructionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for admin routes
        if ($request->is('admin/*') || $request->is('admin')) {
            return $next($request);
        }

        // Only allow logout route - block all other auth routes including login and password reset
        if ($request->is('logout')) {
            return $next($request);
        }
        
        // Allow API routes, assets, and CSRF token refresh
        if ($request->is('api/*') || $request->is('sanctum/*') || $request->is('broadcasting/*') || $request->is('csrf-token')) {
            return $next($request);
        }

        // Skip for admin users - moved after settings check for efficiency
        // This will be checked again below if under construction is active

        // Check if under construction mode is active
        $settings = Cache::remember('under_construction_settings', 300, function () {
            return UnderConstructionSetting::first();
        });

        if ($settings && $this->isUnderConstruction($settings)) {
            // Allow admin users to access the site normally
            if ($request->user() && $request->user()->hasRole('admin')) {
                return $next($request);
            }
            
            return Inertia::render('UnderConstruction', [
                'blurb' => $settings->blurb ?? '<p>We are currently updating our website. Please check back soon!</p>',
            ])->toResponse($request)->setStatusCode(503);
        }

        return $next($request);
    }

    private function isUnderConstruction(UnderConstructionSetting $settings): bool
    {
        if (!$settings->is_enabled) {
            return false;
        }

        $now = now();

        // Check if we're within the date range if dates are set
        if ($settings->start_date && $now->lt($settings->start_date)) {
            return false;
        }

        if ($settings->end_date && $now->gt($settings->end_date)) {
            return false;
        }

        return true;
    }
}
