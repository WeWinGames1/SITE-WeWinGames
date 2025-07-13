<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUploadSize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the content length exceeds our limit
        $maxSize = 25 * 1024 * 1024; // 25MB in bytes
        $contentLength = $request->server('CONTENT_LENGTH');
        
        if ($contentLength && $contentLength > $maxSize) {
            return response()->json([
                'message' => 'The uploaded file is too large. Maximum size is 25MB.'
            ], 413);
        }
        
        return $next($request);
    }
}