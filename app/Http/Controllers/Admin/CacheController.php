<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudflareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    /**
     * Clear all application caches
     */
    public function clear(Request $request)
    {
        // Check if user is authenticated and is admin
        if (!auth()->check() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Clear various caches
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            
            // Clear compiled classes
            if (file_exists(base_path('bootstrap/cache/compiled.php'))) {
                @unlink(base_path('bootstrap/cache/compiled.php'));
            }
            
            // Clear any custom caches
            Cache::flush();

            // Clear Cloudflare cache if enabled
            $cloudflareMessage = '';
            $cloudflareService = new CloudflareService();
            $cloudflareResult = $cloudflareService->purgeEverything();
            
            if (config('cloudflare.enabled')) {
                if ($cloudflareResult['success']) {
                    $cloudflareMessage = ' Cloudflare cache also purged.';
                } else {
                    $cloudflareMessage = ' Cloudflare cache purge failed: ' . $cloudflareResult['message'];
                }
            }

            // Log the action
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'cloudflare' => $cloudflareResult,
                ])
                ->log('Admin cleared all caches');

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully!' . $cloudflareMessage
            ]);
        } catch (\Exception $e) {
            \Log::error('Cache clear failed', [
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear some caches. Please check the logs.'
            ], 500);
        }
    }
}