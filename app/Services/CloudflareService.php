<?php

namespace App\Services;

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
        if (!$this->enabled) {
            return [
                'success' => true,
                'message' => 'Cloudflare integration is disabled'
            ];
        }

        if (!$this->email || !$this->apiKey || !$this->zoneId) {
            Log::warning('Cloudflare credentials not configured');
            return [
                'success' => false,
                'message' => 'Cloudflare credentials not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/zones/{$this->zoneId}/purge_cache", [
                'purge_everything' => true
            ]);

            $result = $response->json();

            if ($response->successful() && $result['success'] ?? false) {
                Log::info('Cloudflare cache purged successfully', [
                    'zone_id' => $this->zoneId,
                    'user' => auth()->id()
                ]);

                return [
                    'success' => true,
                    'message' => 'Cloudflare cache purged successfully'
                ];
            }

            Log::error('Cloudflare cache purge failed', [
                'response' => $result,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare cache purge failed: ' . ($result['errors'][0]['message'] ?? 'Unknown error')
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare API error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Purge specific URLs from Cloudflare cache
     *
     * @param array $urls
     * @return array
     */
    public function purgeUrls(array $urls)
    {
        if (!$this->enabled) {
            return [
                'success' => true,
                'message' => 'Cloudflare integration is disabled'
            ];
        }

        if (!$this->email || !$this->apiKey || !$this->zoneId) {
            return [
                'success' => false,
                'message' => 'Cloudflare credentials not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/zones/{$this->zoneId}/purge_cache", [
                'files' => $urls
            ]);

            $result = $response->json();

            if ($response->successful() && $result['success'] ?? false) {
                Log::info('Cloudflare URLs purged successfully', [
                    'urls' => $urls,
                    'zone_id' => $this->zoneId,
                    'user' => auth()->id()
                ]);

                return [
                    'success' => true,
                    'message' => 'Cloudflare URLs purged successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Cloudflare URL purge failed: ' . ($result['errors'][0]['message'] ?? 'Unknown error')
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare API error', [
                'error' => $e->getMessage(),
                'urls' => $urls
            ]);

            return [
                'success' => false,
                'message' => 'Cloudflare API error: ' . $e->getMessage()
            ];
        }
    }
}