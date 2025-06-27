<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields to exclude from sanitization
     */
    private array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'content', // Rich text editor content
        'html',
        'markdown',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize all input data
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);

        // Sanitize route parameters only if route exists
        if ($request->route()) {
            $routeParams = $request->route()->parameters();
            $sanitizedParams = $this->sanitizeArray($routeParams);
            foreach ($sanitizedParams as $key => $value) {
                $request->route()->setParameter($key, $value);
            }
        }

        return $next($request);
    }

    /**
     * Sanitize an array recursively
     */
    private function sanitizeArray(array $data, string $parentKey = ''): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $fullKey = $parentKey ? "{$parentKey}.{$key}" : $key;

            if ($this->shouldSkip($fullKey)) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $fullKey);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a string value
     */
    private function sanitizeString(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Strip tags (except for rich text fields)
        $value = strip_tags($value);

        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove any remaining control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return $value;
    }

    /**
     * Check if field should be skipped from sanitization
     */
    private function shouldSkip(string $field): bool
    {
        foreach ($this->except as $pattern) {
            if ($field === $pattern || str_ends_with($field, ".{$pattern}")) {
                return true;
            }
        }

        return false;
    }
}