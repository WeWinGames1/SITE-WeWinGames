<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SimpleCacheService
{
    /**
     * Cache TTL values in seconds
     */
    const TTL_SHORT = 300;     // 5 minutes
    const TTL_MEDIUM = 1800;   // 30 minutes
    const TTL_LONG = 3600;     // 1 hour
    const TTL_DAY = 86400;     // 24 hours
    
    /**
     * Common cache keys
     */
    const KEY_SPORTS_LIST = 'sports:list';
    const KEY_OPERATORS_LIST = 'operators:list';
    const KEY_CUSTOMER_STATS = 'customers:stats';
    const KEY_BET_STATS = 'bets:stats';
    const KEY_DASHBOARD_STATS = 'dashboard:stats';
    const KEY_TESTIMONIALS = 'testimonials:published';
    
    /**
     * Remember a query result with optional user context
     */
    public static function rememberQuery(string $key, int $ttl, callable $callback, bool $includeUser = false)
    {
        // Add user context to cache key for personalized data
        if ($includeUser && auth()->check()) {
            $key = "{$key}:user:" . auth()->id();
        }
        
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed, executing callback directly', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // If cache fails, execute callback directly
            return $callback();
        }
    }
    
    /**
     * Clear cache by prefix pattern
     */
    public static function clearByPrefix(string $prefix): void
    {
        try {
            // For Redis driver
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::connection()->keys(Cache::getPrefix() . $prefix . '*');
                foreach ($keys as $key) {
                    Cache::forget(str_replace(Cache::getPrefix(), '', $key));
                }
            }
        } catch (\Exception $e) {
            Log::warning('Cache clear by prefix failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Invalidate related caches when data changes
     */
    public static function invalidateRelated(string $entity): void
    {
        switch ($entity) {
            case 'bet':
                Cache::forget(self::KEY_BET_STATS);
                Cache::forget(self::KEY_DASHBOARD_STATS);
                self::clearByPrefix('bets:');
                break;
                
            case 'user':
                Cache::forget(self::KEY_CUSTOMER_STATS);
                Cache::forget(self::KEY_DASHBOARD_STATS);
                self::clearByPrefix('users:');
                break;
                
            case 'testimonial':
                Cache::forget(self::KEY_TESTIMONIALS);
                break;
                
            case 'sport':
                Cache::forget(self::KEY_SPORTS_LIST);
                self::clearByPrefix('sports:');
                break;
                
            case 'operator':
                Cache::forget(self::KEY_OPERATORS_LIST);
                self::clearByPrefix('operators:');
                break;
        }
    }
}