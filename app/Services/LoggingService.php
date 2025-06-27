<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class LoggingService
{
    /**
     * Log API request
     */
    public function logApiRequest(string $endpoint, array $data = []): void
    {
        $context = $this->getRequestContext();
        $context['endpoint'] = $endpoint;
        $context['request_data'] = $this->sanitizeData($data);

        Log::channel('api')->info('API Request', $context);
    }

    /**
     * Log API response
     */
    public function logApiResponse(string $endpoint, int $statusCode, array $response = []): void
    {
        $context = $this->getRequestContext();
        $context['endpoint'] = $endpoint;
        $context['status_code'] = $statusCode;
        $context['response_size'] = strlen(json_encode($response));

        $logLevel = $statusCode >= 400 ? 'error' : 'info';
        Log::channel('api')->$logLevel('API Response', $context);
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        $context = $this->getRequestContext();
        $context['event'] = $event;
        $context['event_data'] = $this->sanitizeData($data);

        Log::channel('security')->warning('Security Event', $context);
    }

    /**
     * Log business event
     */
    public function logBusinessEvent(string $event, array $data = []): void
    {
        $context = $this->getRequestContext();
        $context['event'] = $event;
        $context['event_data'] = $this->sanitizeData($data);

        Log::channel('business')->info('Business Event', $context);
    }

    /**
     * Log performance metric
     */
    public function logPerformanceMetric(string $metric, float $value, array $tags = []): void
    {
        $context = [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('performance')->info('Performance Metric', $context);
    }

    /**
     * Log database query
     */
    public function logDatabaseQuery(string $query, array $bindings, float $time): void
    {
        if ($time < 100) { // Only log slow queries (> 100ms)
            return;
        }

        $context = [
            'query' => $query,
            'bindings' => $this->sanitizeData($bindings),
            'execution_time' => $time,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('database')->warning('Slow Query', $context);
    }

    /**
     * Log error with context
     */
    public function logError(\Throwable $exception, array $context = []): void
    {
        $errorContext = array_merge($this->getRequestContext(), [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ]);

        Log::channel('errors')->error('Application Error', $errorContext);
    }

    /**
     * Get request context
     */
    private function getRequestContext(): array
    {
        $request = Request::instance();
        $user = Auth::user();

        return [
            'request_id' => $request->header('X-Request-ID') ?? uniqid('req_'),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Sanitize sensitive data
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv',
            'ssn',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Log audit trail
     */
    public function logAudit(string $action, string $model, int $modelId, array $changes = []): void
    {
        $context = $this->getRequestContext();
        $context['action'] = $action;
        $context['model'] = $model;
        $context['model_id'] = $modelId;
        $context['changes'] = $this->sanitizeData($changes);

        Log::channel('audit')->info('Audit Trail', $context);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
            'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024, // MB
            'cpu_usage' => sys_getloadavg()[0],
            'request_time' => microtime(true) - LARAVEL_START,
        ];
    }
}