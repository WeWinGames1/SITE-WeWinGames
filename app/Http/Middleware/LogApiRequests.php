<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LoggingService;

class LogApiRequests
{
    public function __construct(
        private LoggingService $loggingService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Generate unique request ID
        $requestId = uniqid('req_');
        $request->headers->set('X-Request-ID', $requestId);

        // Log incoming request
        $this->loggingService->logApiRequest(
            $request->path(),
            $request->all()
        );

        // Process request
        $response = $next($request);

        // Calculate request duration
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Log response
        $this->loggingService->logApiResponse(
            $request->path(),
            $response->getStatusCode(),
            $response instanceof \Illuminate\Http\JsonResponse 
                ? $response->getData(true) 
                : []
        );

        // Log performance metric
        $this->loggingService->logPerformanceMetric(
            'api.request.duration',
            $duration,
            [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
            ]
        );

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');

        return $response;
    }
}