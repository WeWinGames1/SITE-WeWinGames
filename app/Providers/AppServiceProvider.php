<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
