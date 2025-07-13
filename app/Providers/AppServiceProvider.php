<?php

namespace App\Providers;

use App\Channels\LoggedMailChannel;
use App\Listeners\LogSendingEmail;
use App\Listeners\LogSentEmail;
use App\Services\CacheService;
use App\Services\RateLimiterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache service as singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService;
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
                'VAPID_PUBLIC_KEY' => env('VAPID_PUBLIC_KEY'),
            ],
        ]);

        // Configure rate limiters
        $rateLimiterService = new RateLimiterService;
        $rateLimiterService->configure();

        // Configure model settings
        Model::preventLazyLoading(! $this->app->isProduction());

        // Register email logging event listeners
        Event::listen(MessageSending::class, LogSendingEmail::class);
        Event::listen(MessageSent::class, LogSentEmail::class);
        
        // Override the default mail channel with our logged version
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('mail', function ($app) {
                return new LoggedMailChannel(
                    $app->make('mail.manager')
                );
            });
        });

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
