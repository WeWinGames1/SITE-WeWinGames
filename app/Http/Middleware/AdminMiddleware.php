<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has admin role
        if (! $request->user() || ! $request->user()->hasRole('admin')) {
            // For Inertia requests, redirect to admin login
            if ($request->header('X-Inertia')) {
                return redirect()->route('admin.login')
                    ->with('error', 'You must be logged in as an administrator to access this area.');
            }
            
            // For regular requests, abort with 403
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
