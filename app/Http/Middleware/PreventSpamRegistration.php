<?php

namespace App\Http\Middleware;

use App\Services\CloudflareService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PreventSpamRegistration
{
    protected CloudflareService $cloudflare;

    public function __construct(CloudflareService $cloudflare)
    {
        $this->cloudflare = $cloudflare;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get real IP from Cloudflare
        $ip = $this->cloudflare->getRealIp($request);
        $userAgent = $request->userAgent();
        $fingerprint = $this->generateFingerprint($request);

        // Quick check for Cloudflare suspicious signals (only if enabled)
        if (config('services.cloudflare.security_enabled', false) && $this->cloudflare->isSuspiciousRequest($request)) {
            Log::warning('Registration blocked by Cloudflare signals', [
                'cf_data' => $this->cloudflare->getSecuritySummary($request),
            ]);

            return response()->json([
                'message' => 'Access denied. Please try again later.',
            ], 403);
        }

        // IP-based rate limiting
        $ipKey = "registration_attempts_ip:{$ip}";
        $ipAttempts = Cache::get($ipKey, 0);

        if ($ipAttempts >= 3) {
            Log::warning('Registration blocked - Too many attempts from IP', [
                'ip' => $ip,
                'attempts' => $ipAttempts,
            ]);

            return response()->json([
                'message' => 'Too many registration attempts. Please try again later.',
            ], 429);
        }

        // Device fingerprint rate limiting
        $fingerprintKey = "registration_attempts_fingerprint:{$fingerprint}";
        $fingerprintAttempts = Cache::get($fingerprintKey, 0);

        if ($fingerprintAttempts >= 5) {
            Log::warning('Registration blocked - Too many attempts from device', [
                'fingerprint' => $fingerprint,
                'attempts' => $fingerprintAttempts,
            ]);

            return response()->json([
                'message' => 'Too many registration attempts from this device. Please try again later.',
            ], 429);
        }

        // Check for suspicious patterns
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Registration blocked - Suspicious request pattern', [
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'message' => 'Registration temporarily unavailable. Please try again later.',
            ], 403);
        }

        // Increment counters on POST requests
        if ($request->isMethod('POST')) {
            Cache::increment($ipKey);
            Cache::expire($ipKey, 3600); // 1 hour

            Cache::increment($fingerprintKey);
            Cache::expire($fingerprintKey, 86400); // 24 hours
        }

        return $next($request);
    }

    /**
     * Generate a device fingerprint based on request characteristics
     */
    private function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->getHttpHost(),
        ];

        return md5(implode('|', array_filter($components)));
    }

    /**
     * Check for suspicious request patterns
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // Check for bot user agents
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'ruby', 'perl', 'go-http-client',
        ];

        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        // Check for missing or suspicious headers
        if (! $request->hasHeader('Accept-Language') ||
            ! $request->hasHeader('Accept-Encoding') ||
            empty($userAgent)) {
            return true;
        }

        // Check for rapid successive requests
        $recentKey = "recent_registration_check:{$request->ip()}";
        if (Cache::has($recentKey)) {
            return true;
        }
        Cache::put($recentKey, true, 5); // 5 seconds

        return false;
    }
}
