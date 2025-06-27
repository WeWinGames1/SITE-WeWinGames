<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BetController;
use App\Http\Controllers\Api\V1\BetApiController;

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
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
            'meta' => ['version' => 'v1']
        ]);
    });

    // Bet endpoints
    Route::apiResource('bets', BetApiController::class);
    Route::get('bets/statistics', [BetApiController::class, 'statistics']);

    // Push notification endpoints
    Route::prefix('push')->group(function () {
        Route::post('/subscribe', function (Request $request) {
            $request->validate([
                'endpoint' => 'required|string',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string'
            ]);
            
            $request->user()->updatePushSubscription(
                $request->endpoint,
                $request->keys['p256dh'],
                $request->keys['auth']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Push subscription updated successfully',
                'meta' => ['version' => 'v1']
            ]);
        });

        Route::post('/unsubscribe', function (Request $request) {
            $request->user()->removePushSubscription();
            
            return response()->json([
                'success' => true,
                'message' => 'Push subscription removed successfully',
                'meta' => ['version' => 'v1']
            ]);
        });

        Route::get('/subscription', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => $request->user()->getPushSubscription(),
                'meta' => ['version' => 'v1']
            ]);
        });
    });
});

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
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Legacy push notification endpoints
    Route::post('/push/subscribe', function (Request $request) {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string'
        ]);
        
        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );
        
        return response()->json(['success' => true]);
    });
    
    Route::post('/push/unsubscribe', function (Request $request) {
        $request->user()->removePushSubscription();
        return response()->json(['success' => true]);
    });
    
    Route::get('/push/subscription', function (Request $request) {
        return response()->json($request->user()->getPushSubscription());
    });
});



