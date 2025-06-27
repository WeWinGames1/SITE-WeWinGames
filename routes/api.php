<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BetController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bets', [BetController::class, 'store']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/bets', [BetController::class, 'getAllBets']);
    Route::put('/bets/{bet}', [BetController::class, 'update']);
    Route::delete('/bets/{bet}', [BetController::class, 'destroy']);
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



