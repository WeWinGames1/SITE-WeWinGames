<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Check if the session is valid
     */
    public function check(Request $request)
    {
        $sessionId = session()->getId();
        $csrfToken = csrf_token();
        
        return response()->json([
            'valid' => true,
            'session_id' => $sessionId,
            'csrf_token' => $csrfToken,
            'lifetime' => config('session.lifetime'),
            'remaining' => $this->getSessionRemainingLifetime($request),
        ]);
    }
    
    /**
     * Get the remaining lifetime of the session in minutes
     */
    private function getSessionRemainingLifetime(Request $request)
    {
        $lastActivity = $request->session()->get('_last_activity', time());
        $lifetime = config('session.lifetime') * 60; // Convert to seconds
        $elapsed = time() - $lastActivity;
        $remaining = max(0, $lifetime - $elapsed);
        
        return round($remaining / 60); // Return in minutes
    }
}