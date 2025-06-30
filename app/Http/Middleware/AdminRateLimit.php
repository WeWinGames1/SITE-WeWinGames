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
        $key = $this->resolveRequestSignature($request, $type);
        
        $maxAttempts = $this->getMaxAttempts($type);
        $decayMinutes = $this->getDecayMinutes($type);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response, 
            $maxAttempts,
            RateLimiter::attempts($key),
            RateLimiter::availableIn($key)
        );
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
    protected function getMaxAttempts(string $type): int
    {
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
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $seconds = RateLimiter::availableIn($key);
        
        return response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $seconds,
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