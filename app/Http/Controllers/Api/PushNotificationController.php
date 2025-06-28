<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushNotificationController extends Controller
{
    /**
     * Subscribe to push notifications (legacy endpoint).
     */
    public function subscribe(Request $request): JsonResponse
    {
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
    }

    /**
     * Unsubscribe from push notifications (legacy endpoint).
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->user()->removePushSubscription();
        return response()->json(['success' => true]);
    }

    /**
     * Get current push subscription (legacy endpoint).
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->getPushSubscription());
    }
}