<?php

use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\BetApiController;
use App\Http\Controllers\Api\V1\PushNotificationController as V1PushNotificationController;
use App\Http\Controllers\Api\V1\UserApiController;
use App\Http\Controllers\BetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API Version 1 Routes - Preferred for new integrations
|
*/

// API Version 1 - RESTful endpoints with better structure
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // User endpoints
    Route::get('/user', [UserApiController::class, 'show']);

    // Bet endpoints
    Route::apiResource('bets', BetApiController::class);
    Route::get('bets/statistics', [BetApiController::class, 'statistics']);

    // Push notification endpoints
    Route::prefix('push')->group(function () {
        Route::post('/subscribe', [V1PushNotificationController::class, 'subscribe']);
        Route::post('/unsubscribe', [V1PushNotificationController::class, 'unsubscribe']);
        Route::get('/subscription', [V1PushNotificationController::class, 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Ticker endpoint - no auth required
Route::get('/ticker-bets', [BetController::class, 'getTickerBets']);

/*
|--------------------------------------------------------------------------
| Legacy API Routes - Maintained for backward compatibility
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Legacy bet endpoints
    Route::post('/bets', [BetController::class, 'store']);
    Route::get('/bets', [BetController::class, 'getAllBets']);
    Route::put('/bets/{bet}', [BetController::class, 'update']);
    Route::delete('/bets/{bet}', [BetController::class, 'destroy']);

    // Legacy user endpoint
    Route::get('/user', [UserController::class, 'show']);

    // Legacy push notification endpoints
    Route::post('/push/subscribe', [PushNotificationController::class, 'subscribe']);
    Route::post('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe']);
    Route::get('/push/subscription', [PushNotificationController::class, 'show']);
});
