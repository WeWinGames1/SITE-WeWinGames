<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CloudflareService
{
    /**
     * Verify Cloudflare Turnstile response
     */
    public function verifyTurnstile(string $token, string $ip): array
    {
        if (!config('services.turnstile.enabled')) {
            return ['success' => true, 'score' => 1.0];
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (!$response->successful()) {
            Log::error('Turnstile verification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['success' => false, 'score' => 0];
        }

        $data = $response->json();
        
        return [
            'success' => $data['success'] ?? false,
            'score' => $data['success'] ? 1.0 : 0,
            'error_codes' => $data['error-codes'] ?? [],
            'challenge_ts' => $data['challenge_ts'] ?? null,
            'hostname' => $data['hostname'] ?? null,
        ];
    }

    /**
     * Get Cloudflare headers and data from request
     */
    public function getCloudflareData(Request $request): array
    {
        return [
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'cf_ipcountry' => $request->header('CF-IPCountry'),
            'cf_ray' => $request->header('CF-RAY'),
            'cf_visitor' => json_decode($request->header('CF-Visitor', '{}'), true),
            'cf_bot_score' => $request->header('CF-Bot-Score'),
            'cf_verified_bot' => $request->header('CF-Verified-Bot'),
            'cf_ja3_hash' => $request->header('CF-JA3-Hash'),
            'cf_threat_score' => $request->header('CF-Threat-Score'),
        ];
    }

    /**
     * Get real IP address considering Cloudflare proxy
     */
    public function getRealIp(Request $request): string
    {
        if (config('services.cloudflare.enabled') && $request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }
        
        return $request->ip();
    }

    /**
     * Get country code from Cloudflare headers
     */
    public function getCountryCode(Request $request): ?string
    {
        return $request->header('CF-IPCountry');
    }

    /**
     * Check if request is from a blocked country
     */
    public function isBlockedCountry(Request $request): bool
    {
        // If Cloudflare security is disabled, don't check
        if (!config('services.cloudflare.security_enabled', false)) {
            return false;
        }
        
        $country = $this->getCountryCode($request);
        
        if (!$country) {
            return false;
        }

        // Get blocked countries from cache or config
        $blockedCountries = Cache::remember('blocked_countries', 3600, function () {
            return config('services.cloudflare.blocked_countries', []);
        });

        return in_array($country, $blockedCountries);
    }

    /**
     * Check if request appears to be from a bot based on Cloudflare signals
     */
    public function isSuspiciousRequest(Request $request): bool
    {
        // If Cloudflare security is disabled, don't check
        if (!config('services.cloudflare.security_enabled', false)) {
            return false;
        }
        
        $cfData = $this->getCloudflareData($request);
        
        // Check bot score if available (Enterprise feature)
        if (isset($cfData['cf_bot_score'])) {
            $botScore = (int) $cfData['cf_bot_score'];
            if ($botScore < 30) { // Score below 30 is likely a bot
                Log::warning('Low Cloudflare bot score detected', [
                    'score' => $botScore,
                    'ip' => $this->getRealIp($request),
                ]);
                return true;
            }
        }

        // Check if it's a verified bot (like Googlebot)
        if ($cfData['cf_verified_bot'] === 'true') {
            return true; // Block even verified bots from registration
        }

        // Check threat score (if available)
        if (isset($cfData['cf_threat_score'])) {
            $threatScore = (int) $cfData['cf_threat_score'];
            if ($threatScore > 30) { // Higher score = more threatening
                Log::warning('High Cloudflare threat score detected', [
                    'score' => $threatScore,
                    'ip' => $this->getRealIp($request),
                ]);
                return true;
            }
        }

        // Check for Tor exit nodes (country code T1)
        if ($cfData['cf_ipcountry'] === 'T1') {
            Log::warning('Tor exit node detected', [
                'ip' => $this->getRealIp($request),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get security summary for logging
     */
    public function getSecuritySummary(Request $request): array
    {
        $cfData = $this->getCloudflareData($request);
        
        return [
            'ip' => $this->getRealIp($request),
            'country' => $cfData['cf_ipcountry'] ?? 'Unknown',
            'bot_score' => $cfData['cf_bot_score'] ?? 'N/A',
            'threat_score' => $cfData['cf_threat_score'] ?? 'N/A',
            'verified_bot' => $cfData['cf_verified_bot'] ?? false,
            'ray_id' => $cfData['cf_ray'] ?? null,
            'is_https' => $cfData['cf_visitor']['scheme'] ?? false === 'https',
            'ja3_hash' => $cfData['cf_ja3_hash'] ?? null,
        ];
    }
}