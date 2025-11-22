<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspiciousRequests
{
    /**
     * Suspicious patterns to block.
     *
     * @var array<string>
     */
    protected $suspiciousPatterns = [
        '.php',           // Block direct PHP file access (except index.php which is handled by server)
        '.env',           // Block environment file access
        '.git',           // Block git directory access
        'wp-',            // Block WordPress specific paths
        'xmlrpc',         // Block XML-RPC
        'autodiscover',   // Block Exchange autodiscover
        'phpmyadmin',     // Block PHPMyAdmin
        'composer.json',  // Block composer.json
        'composer.lock',  // Block composer.lock
        'package.json',   // Block package.json
        'storage/logs',   // Block log access
        'phpunit',        // Block PHPUnit
        'server-status',  // Block Apache server status
        '/.aws/',         // Block AWS credentials
        '/.ssh/',         // Block SSH keys
    ];

    /**
     * Allowed PHP files (if any specific ones are needed outside of index.php)
     *
     * @var array<string>
     */
    protected $allowedFiles = [
        // 'api.php', 
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // Check for suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (str_contains(strtolower($path), strtolower($pattern))) {
                // If it's a .php file check, make sure it's not in the allowed list
                if ($pattern === '.php') {
                    foreach ($this->allowedFiles as $allowed) {
                        if (str_ends_with($path, $allowed)) {
                            return $next($request);
                        }
                    }

                    // Allow root path (which is handled by index.php)
                    if ($path === '/') {
                        return $next($request);
                    }
                }

                // Return 404 to confuse bots (or 403 if you prefer to be explicit)
                abort(404);
            }
        }

        return $next($request);
    }
}
