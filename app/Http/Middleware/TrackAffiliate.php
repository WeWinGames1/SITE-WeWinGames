<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Affiliate;

class TrackAffiliate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if affiliate parameter is in query string
        if ($request->has('affiliate')) {
            $affiliateCode = $request->query('affiliate');
            
            // Verify affiliate exists and is active
            $affiliate = Affiliate::where('code', $affiliateCode)
                ->where('is_active', true)
                ->first();

            if ($affiliate) {
                // Set cookie for 30 days
                $response->cookie('affiliate_code', $affiliateCode, 60 * 24 * 30);
            }
        }

        return $response;
    }
}
