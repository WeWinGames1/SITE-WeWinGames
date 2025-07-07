<?php

namespace App\Services;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService
{
    /**
     * Configure rate limiters for the application
     */
    public function configure(): void
    {
        $this->configureAuthLimiters();
        $this->configureApiLimiters();
        $this->configureAdminLimiters();
        $this->configurePublicLimiters();
    }

    /**
     * Configure authentication rate limiters
     */
    private function configureAuthLimiters(): void
    {
        // Login rate limiter
        RateLimiter::for('login', function (Request $request) {
            $config = config('ratelimit.limits.auth.login');
            $key = $this->resolveRequestKey($request, 'login');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key)->response(function (Request $request, array $headers) use ($config) {
                Log::warning('Login rate limit exceeded', [
                    'ip' => $request->ip(),
                    'email' => $request->input('email'),
                ]);

                return response()->json([
                    'message' => __('ratelimit.messages.auth_blocked', ['minutes' => $config['block_duration']]),
                    'retry_after' => $headers['Retry-After'] ?? null,
                ], 429, $headers);
            });
        });

        // Registration rate limiter
        RateLimiter::for('register', function (Request $request) {
            $config = config('ratelimit.limits.auth.register');
            $key = $this->resolveRequestKey($request, 'register');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key);
        });

        // Password reset rate limiter
        RateLimiter::for('password-reset', function (Request $request) {
            $config = config('ratelimit.limits.auth.password_reset');
            $key = $request->input('email', $request->ip());

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key);
        });
    }

    /**
     * Configure API rate limiters
     */
    private function configureApiLimiters(): void
    {
        // Default API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $config = config('ratelimit.limits.api.default');
            $user = $request->user();

            if ($user) {
                // Authenticated users get higher limits
                return Limit::perMinute($config['attempts'] * 2)
                    ->by($user->id)
                    ->response($this->rateLimitResponse());
            }

            return Limit::perMinute($config['attempts'])
                ->by($request->ip())
                ->response($this->rateLimitResponse());
        });

        // Search API rate limiter
        RateLimiter::for('api-search', function (Request $request) {
            $config = config('ratelimit.limits.api.search');
            $key = $this->resolveRequestKey($request, 'search');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key);
        });

        // Export API rate limiter
        RateLimiter::for('api-export', function (Request $request) {
            $config = config('ratelimit.limits.api.export');
            $key = $this->resolveRequestKey($request, 'export');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key);
        });

        // Import API rate limiter
        RateLimiter::for('api-import', function (Request $request) {
            $config = config('ratelimit.limits.api.import');
            $key = $this->resolveRequestKey($request, 'import');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($key);
        });
    }

    /**
     * Configure admin rate limiters
     */
    private function configureAdminLimiters(): void
    {
        // Admin default rate limiter
        RateLimiter::for('admin', function (Request $request) {
            $config = config('ratelimit.limits.admin.default');
            $user = $request->user();

            if (! $user || ! $user->hasRole('admin')) {
                // Non-admins get stricter limits
                return Limit::perMinute(10)->by($request->ip());
            }

            return Limit::perMinute($config['attempts'])->by($user->id);
        });

        // User management rate limiter
        RateLimiter::for('admin-users', function (Request $request) {
            $config = config('ratelimit.limits.admin.user_management');
            $user = $request->user();

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($user ? $user->id : $request->ip());
        });

        // Bulk operations rate limiter
        RateLimiter::for('admin-bulk', function (Request $request) {
            $config = config('ratelimit.limits.admin.bulk_operations');
            $user = $request->user();

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($user ? $user->id : $request->ip());
        });
    }

    /**
     * Configure public rate limiters
     */
    private function configurePublicLimiters(): void
    {
        // Public pages rate limiter
        RateLimiter::for('public', function (Request $request) {
            $config = config('ratelimit.limits.public.default');

            return Limit::perMinute($config['attempts'])->by($request->ip());
        });

        // Contact form rate limiter
        RateLimiter::for('contact', function (Request $request) {
            $config = config('ratelimit.limits.public.contact');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['attempts']
            )->by($request->ip());
        });
    }

    /**
     * Resolve request key for rate limiting
     */
    private function resolveRequestKey(Request $request, string $prefix = ''): string
    {
        $user = $request->user();

        if ($user) {
            return $prefix.':user:'.$user->id;
        }

        return $prefix.':ip:'.$request->ip();
    }

    /**
     * Get rate limit response callback
     */
    private function rateLimitResponse(): callable
    {
        return function (Request $request, array $headers) {
            Log::warning('API rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'message' => config('ratelimit.messages.too_many_attempts'),
                'retry_after' => $headers['Retry-After'] ?? null,
            ], 429, $headers);
        };
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIpWhitelisted(string $ip): bool
    {
        $whitelist = array_filter(
            explode(',', config('ratelimit.ip_limits.whitelist', ''))
        );

        return in_array($ip, $whitelist);
    }

    /**
     * Check if IP is blacklisted
     */
    public function isIpBlacklisted(string $ip): bool
    {
        $blacklist = array_filter(
            explode(',', config('ratelimit.ip_limits.blacklist', ''))
        );

        return in_array($ip, $blacklist);
    }
}
