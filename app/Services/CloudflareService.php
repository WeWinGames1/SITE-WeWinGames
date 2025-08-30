<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $email;

    protected $apiKey;

    protected $zoneId;

    protected $apiUrl;

    protected $enabled;

    public function __construct()
    {
        $this->enabled = config('cloudflare.enabled');
        $this->email = config('cloudflare.email');
        $this->apiKey = config('cloudflare.api_key');
        $this->zoneId = config('cloudflare.zone_id');
        $this->apiUrl = config('cloudflare.api_url');
    }

    /**
     * Purge all cache from Cloudflare
     *
     * @return array
     */
    public function purgeEverything()
    {
        if (! $this->enabled) {
            return [
                'success' => true,
                'message' => 'Cloudflare integration is disabled',
            ];
        }

        if (! $this->email || ! $this->apiKey || ! $this->zoneId) {
            Log::warning('Cloudflare credentials not configured');

            return [
                'success' => false,
                'message' => 'Cloudflare credentials not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/zones/{$this->zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

            $result = $response->json();

            if ($response->successful() && $result['success'] ?? false) {
                Log::info('Cloudflare cache purged successfully', [
                    'zone_id' => $this->zoneId,
                    'user' => auth()->id(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Cloudflare cache purged successfully',
                ];
            }

            Log::error('Cloudflare cache purge failed', [
                'response' => $result,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare cache purge failed: '.($result['errors'][0]['message'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare API error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Purge specific URLs from Cloudflare cache
     *
     * @return array
     */
    public function purgeUrls(array $urls)
    {
        if (! $this->enabled) {
            return [
                'success' => true,
                'message' => 'Cloudflare integration is disabled',
            ];
        }

        if (! $this->email || ! $this->apiKey || ! $this->zoneId) {
            return [
                'success' => false,
                'message' => 'Cloudflare credentials not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/zones/{$this->zoneId}/purge_cache", [
                'files' => $urls,
            ]);

            $result = $response->json();

            if ($response->successful() && $result['success'] ?? false) {
                Log::info('Cloudflare URLs purged successfully', [
                    'urls' => $urls,
                    'zone_id' => $this->zoneId,
                    'user' => auth()->id(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Cloudflare URLs purged successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Cloudflare URL purge failed: '.($result['errors'][0]['message'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare API error', [
                'error' => $e->getMessage(),
                'urls' => $urls,
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare API error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get the real IP address from Cloudflare headers
     */
    public function getRealIp(Request $request): string
    {
        // Check for Cloudflare's CF-Connecting-IP header first
        if ($request->hasHeader('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Fallback to X-Forwarded-For
        if ($request->hasHeader('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));

            return trim($ips[0]);
        }

        // Fallback to standard IP
        return $request->ip();
    }

    /**
     * Check if request is suspicious based on Cloudflare signals
     */
    public function isSuspiciousRequest(Request $request): bool
    {
        // Check Cloudflare threat score (if available)
        $threatScore = $request->header('CF-Threat-Score');
        if ($threatScore && (int) $threatScore > 30) {
            return true;
        }

        // Check if it's a known bot
        if ($request->hasHeader('CF-Bot-Management')) {
            $botScore = json_decode($request->header('CF-Bot-Management'), true);
            if (isset($botScore['score']) && $botScore['score'] < 30) {
                return true;
            }
        }

        // Check country (optional - you can customize this)
        $country = $request->header('CF-IPCountry');
        $blockedCountries = config('cloudflare.blocked_countries', []);
        if ($country && in_array($country, $blockedCountries)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the request is from a blocked country
     */
    public function isBlockedCountry(Request $request): bool
    {
        $country = $request->header('CF-IPCountry');
        $blockedCountries = config('cloudflare.blocked_countries', []);

        return $country && in_array($country, $blockedCountries);
    }

    /**
     * Get security summary from Cloudflare headers
     */
    public function getSecuritySummary(Request $request): array
    {
        return [
            'ip' => $this->getRealIp($request),
            'country' => $request->header('CF-IPCountry', 'Unknown'),
            'threat_score' => $request->header('CF-Threat-Score', 'N/A'),
            'ray_id' => $request->header('CF-Ray', 'N/A'),
            'visitor_scheme' => $request->header('CF-Visitor', 'N/A'),
            'user_agent' => $request->userAgent(),
            'is_tor' => $request->header('CF-Tor-Exit') === 'true',
        ];
    }

    /**
     * Verify Turnstile token
     */
    public function verifyTurnstile(string $token, string $remoteIp): array
    {
        if (! config('services.turnstile.enabled')) {
            return ['success' => true];
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

            $result = $response->json();

            if (! $response->successful()) {
                Log::error('Turnstile API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'API request failed'];
            }

            if (! ($result['success'] ?? false)) {
                Log::warning('Turnstile verification failed', [
                    'error_codes' => $result['error-codes'] ?? [],
                    'ip' => $remoteIp,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'error' => $e->getMessage(),
                'ip' => $remoteIp,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
