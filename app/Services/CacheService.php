<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    private array $cacheTags = [];

    private int $defaultTtl = 3600; // 1 hour

    private bool $enabled = true;

    public function __construct()
    {
        $this->enabled = config('cache.enabled', true);
    }

    /**
     * Remember a value in cache with tags support
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null, array $tags = []): mixed
    {
        if (! $this->enabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $fullKey = $this->buildKey($key);

        try {
            if (! empty($tags)) {
                return Cache::tags($tags)->remember($fullKey, $ttl, $callback);
            }

            return Cache::remember($fullKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache operation failed, executing callback directly', [
                'key' => $fullKey,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Remember forever with tags support
     */
    public function rememberForever(string $key, \Closure $callback, array $tags = []): mixed
    {
        if (! $this->enabled) {
            return $callback();
        }

        $fullKey = $this->buildKey($key);

        try {
            if (! empty($tags)) {
                return Cache::tags($tags)->rememberForever($fullKey, $callback);
            }

            return Cache::rememberForever($fullKey, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache operation failed, executing callback directly', [
                'key' => $fullKey,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Get cached value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->enabled) {
            return $default;
        }

        $fullKey = $this->buildKey($key);

        return Cache::get($fullKey, $default);
    }

    /**
     * Set cached value
     */
    public function put(string $key, mixed $value, ?int $ttl = null, array $tags = []): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $fullKey = $this->buildKey($key);

        try {
            if (! empty($tags)) {
                Cache::tags($tags)->put($fullKey, $value, $ttl);
            } else {
                Cache::put($fullKey, $value, $ttl);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set cache', [
                'key' => $fullKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete cached value
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->buildKey($key);

        return Cache::forget($fullKey);
    }

    /**
     * Clear cache by tags
     */
    public function clearByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear all cache
     */
    public function clearAll(): bool
    {
        try {
            Cache::flush();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmUp(): void
    {
        Log::info('Starting cache warm-up');

        // Warm up user-related caches
        $this->warmUpUsers();

        // Warm up bet-related caches
        $this->warmUpBets();

        // Warm up configuration caches
        $this->warmUpConfig();

        Log::info('Cache warm-up completed');
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'evicted_keys' => $info['evicted_keys'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Build cache key with prefix
     */
    private function buildKey(string $key): string
    {
        $prefix = config('cache.prefix', 'wewingames');

        return "{$prefix}:{$key}";
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Warm up user-related caches
     */
    private function warmUpUsers(): void
    {
        try {
            // Cache admin users
            app(\App\Repositories\Contracts\UserRepositoryInterface::class)->getAdmins();

            // Cache users with active subscriptions
            app(\App\Repositories\Contracts\UserRepositoryInterface::class)->getUsersWithActiveSubscriptions();

            Log::info('User cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('User cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Warm up bet-related caches
     */
    private function warmUpBets(): void
    {
        try {
            $betRepo = app(\App\Repositories\Contracts\BetRepositoryInterface::class);

            // Cache recent bets
            $betRepo->getRecentBets(20);

            // Cache bet statistics
            $betRepo->getBetStatistics();

            // Cache profitable bets
            $betRepo->getProfitableBets();

            Log::info('Bet cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('Bet cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Warm up configuration caches
     */
    private function warmUpConfig(): void
    {
        try {
            // Cache application settings
            $this->remember('app:settings', function () {
                return [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                    'debug' => config('app.debug'),
                    'url' => config('app.url'),
                    'timezone' => config('app.timezone'),
                ];
            }, 86400); // Cache for 24 hours

            Log::info('Config cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('Config cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Enable caching
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable caching
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if caching is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
