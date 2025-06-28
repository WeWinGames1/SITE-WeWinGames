<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\CacheHeaders;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('blog')
                ->name('blog.')
                ->group(base_path('routes/blog.php'));
            
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
            
            Route::middleware('web')
                ->group(base_path('routes/settings.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
        $middleware->statefulApi();
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        
        // Global middleware
        $middleware->append([
            SecurityHeaders::class,
            SanitizeInput::class,
        ]);
        
        // API middleware
        $middleware->api(append: [
            LogApiRequests::class,
        ]);
        
        // Alias middleware
        $middleware->alias([
            'cache.headers' => CacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log all exceptions
        $exceptions->report(function (\Throwable $e) {
            if (app()->bound(\App\Services\LoggingService::class)) {
                app(\App\Services\LoggingService::class)->logError($e);
            }
        });
    })->create();
