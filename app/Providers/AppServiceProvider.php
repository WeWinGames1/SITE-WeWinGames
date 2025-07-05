<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Services\RateLimiterService;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache service as singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for MySQL key length issue
        Schema::defaultStringLength(191);
        
        // Configure Inertia shared data
        Inertia::share([
            'env' => [
                'SILVER_MONTHLY' => env('SILVER_MONTHLY'),
                'GOLD_MONTHLY' => env('GOLD_MONTHLY'),
                'PLATINUM_MONTHLY' => env('PLATINUM_MONTHLY'),
                'SILVER_WEEKLY' => env('SILVER_WEEKLY'),
                'GOLD_WEEKLY' => env('GOLD_WEEKLY'),
                'PLATINUM_WEEKLY' => env('PLATINUM_WEEKLY'),
                'SILVER_DAILY' => env('SILVER_DAILY'),
                'GOLD_DAILY' => env('GOLD_DAILY'),
                'PLATINUM_DAILY' => env('PLATINUM_DAILY'),
                'VAPID_PUBLIC_KEY' => env('VAPID_PUBLIC_KEY'),
            ],
        ]);

        // Configure rate limiters
        $rateLimiterService = new RateLimiterService();
        $rateLimiterService->configure();

        // Configure model settings
        Model::preventLazyLoading(!$this->app->isProduction());

        // Log slow queries in development
        if ($this->app->isLocal()) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Log queries slower than 100ms
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}
