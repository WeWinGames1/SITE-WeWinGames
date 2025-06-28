<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushNotificationController extends Controller
{
    /**
     * Subscribe to push notifications.
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
        
        return response()->json([
            'success' => true,
            'message' => 'Push subscription updated successfully',
            'meta' => ['version' => 'v1']
        ]);
    }

    /**
     * Unsubscribe from push notifications.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->user()->removePushSubscription();
        
        return response()->json([
            'success' => true,
            'message' => 'Push subscription removed successfully',
            'meta' => ['version' => 'v1']
        ]);
    }

    /**
     * Get current push subscription.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->getPushSubscription(),
            'meta' => ['version' => 'v1']
        ]);
    }
}