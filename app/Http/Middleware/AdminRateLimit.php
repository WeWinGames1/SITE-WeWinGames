<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdminRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        try {
            // BYPASS RATE LIMITING COMPLETELY FOR ADMIN ROUTES
            // If we're in the admin area, the user has already passed authentication and authorization
            if (str_starts_with($request->path(), 'admin/')) {
                return $next($request);
            }
            
            // Check if user is an admin - if so, bypass rate limiting entirely
            $user = $request->user();
            
            // Multiple ways to check for admin status
            $isAdmin = false;
            if ($user) {
                // Check multiple conditions for admin status
                $isAdmin = $user->id === 1 || // Super admin
                    (method_exists($user, 'hasRole') && $user->hasRole('admin')) || // Has admin role
                    (isset($user->is_admin) && $user->is_admin); // Has is_admin flag
                    
                if ($isAdmin) {
                    \Log::info('AdminRateLimit: Admin user bypassing rate limit', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'route' => $request->path(),
                        'type' => $type
                    ]);
                    return $next($request);
                }
            }
            
            $key = $this->resolveRequestSignature($request, $type);
            
            $maxAttempts = $this->getMaxAttempts($type, $request);
            $decayMinutes = $this->getDecayMinutes($type);
            
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                return $this->buildResponse($key, $maxAttempts, $type);
            }
            
            RateLimiter::hit($key, $decayMinutes * 60);
            
            $response = $next($request);
            
            return $this->addHeaders(
                $response, 
                $maxAttempts,
                RateLimiter::attempts($key),
                RateLimiter::availableIn($key)
            );
        } catch (\Exception $e) {
            // If there's any error, log it and return a proper error response
            \Log::error('AdminRateLimit middleware error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'route' => $request->path(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'type' => $type ?? 'unknown'
            ]);
            
            // Return a proper error response instead of throwing a fatal error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An error occurred while processing your request.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            // For non-JSON requests, redirect to an error page
            return response()->view('errors.500', [
                'message' => 'An error occurred while processing your request.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $prefix = 'admin_' . $type . '_';
        
        if ($type === 'login') {
            return $prefix . $request->ip();
        }
        
        if ($user = $request->user()) {
            return $prefix . $user->id . '_' . $request->ip();
        }
        
        return $prefix . $request->ip();
    }
    
    /**
     * Get the maximum number of attempts based on type.
     */
    protected function getMaxAttempts(string $type, Request $request = null): int
    {
        // Check if user is an admin and give them higher limits
        $isAdmin = false;
        $user = $request ? $request->user() : request()->user();
        if ($user) {
            // Check if user has admin role or is user ID 1 (typically the super admin)
            $isAdmin = $user->hasRole('admin') || $user->id === 1;
        }
        
        // Admin gets significantly higher limits or bypasses rate limiting
        if ($isAdmin) {
            return match($type) {
                'login' => 100,      // 100 login attempts
                'api' => 10000,      // 10,000 API calls per minute (virtually unlimited)
                'export' => 1000,    // 1000 exports per hour (virtually unlimited)
                'import' => 1000,    // 1000 imports per hour (virtually unlimited)
                default => 10000,    // 10,000 general requests per minute (virtually unlimited)
            };
        }
        
        // Regular users get standard limits
        return match($type) {
            'login' => 5,        // 5 login attempts
            'api' => 60,         // 60 API calls per minute
            'export' => 10,      // 10 exports per hour
            'import' => 5,       // 5 imports per hour
            default => 100,      // 100 general requests per minute
        };
    }
    
    /**
     * Get the decay minutes based on type.
     */
    protected function getDecayMinutes(string $type): int
    {
        return match($type) {
            'login' => 15,       // 15 minutes for login attempts
            'api' => 1,          // 1 minute for API calls
            'export' => 60,      // 1 hour for exports
            'import' => 60,      // 1 hour for imports
            default => 1,        // 1 minute for general requests
        };
    }
    
    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts, string $type): Response
    {
        $seconds = RateLimiter::availableIn($key);
        
        // Check if user is admin to provide helpful message
        $isAdmin = false;
        if ($user = request()->user()) {
            $isAdmin = $user->hasRole('admin') || $user->id === 1;
        }
        
        $message = 'Too many attempts. Please try again later.';
        
        // Add context-specific messages
        if ($type === 'import') {
            $message = $isAdmin 
                ? "Import rate limit reached (1000 imports per hour for admins). Please wait {$seconds} seconds."
                : "Import rate limit reached (5 imports per hour). Please wait {$seconds} seconds.";
        } elseif ($type === 'export') {
            $message = $isAdmin 
                ? "Export rate limit reached (1000 exports per hour for admins). Please wait {$seconds} seconds."
                : "Export rate limit reached (10 exports per hour). Please wait {$seconds} seconds.";
        } elseif ($type === 'login') {
            $message = "Too many login attempts. Please wait {$seconds} seconds before trying again.";
        }
        
        // For JSON requests (API/AJAX)
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $seconds,
                'limit_type' => $type,
                'is_admin' => $isAdmin,
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }
        
        // For regular web requests, return a nice error page
        return response()->view('errors.429', [
            'message' => $message,
            'retry_after' => $seconds,
            'limit_type' => $type,
            'is_admin' => $isAdmin,
        ], 429)->withHeaders([
            'Retry-After' => $seconds,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }
    
    /**
     * Add the limit headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $attempts, int $retryAfter): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $attempts),
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
        
        return $response;
    }
}