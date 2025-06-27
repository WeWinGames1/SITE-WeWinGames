<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures the rate limiting for various endpoints in the
    | application to prevent abuse and ensure fair usage.
    |
    */

    'enabled' => env('RATE_LIMIT_ENABLED', true),

    'limits' => [
        // Authentication endpoints
        'auth' => [
            'login' => [
                'attempts' => 5,
                'decay_minutes' => 15,
                'block_duration' => 60, // minutes
            ],
            'register' => [
                'attempts' => 3,
                'decay_minutes' => 60,
                'block_duration' => 120,
            ],
            'password_reset' => [
                'attempts' => 3,
                'decay_minutes' => 30,
                'block_duration' => 60,
            ],
        ],

        // API endpoints
        'api' => [
            'default' => [
                'attempts' => 60,
                'decay_minutes' => 1,
            ],
            'search' => [
                'attempts' => 30,
                'decay_minutes' => 1,
            ],
            'export' => [
                'attempts' => 5,
                'decay_minutes' => 60,
            ],
            'import' => [
                'attempts' => 10,
                'decay_minutes' => 60,
            ],
        ],

        // Admin endpoints
        'admin' => [
            'default' => [
                'attempts' => 100,
                'decay_minutes' => 1,
            ],
            'user_management' => [
                'attempts' => 30,
                'decay_minutes' => 5,
            ],
            'bulk_operations' => [
                'attempts' => 5,
                'decay_minutes' => 30,
            ],
        ],

        // Public endpoints
        'public' => [
            'default' => [
                'attempts' => 100,
                'decay_minutes' => 1,
            ],
            'contact' => [
                'attempts' => 3,
                'decay_minutes' => 60,
            ],
        ],
    ],

    // IP-based rate limiting
    'ip_limits' => [
        'enabled' => true,
        'whitelist' => env('RATE_LIMIT_WHITELIST', ''), // Comma-separated IPs
        'blacklist' => env('RATE_LIMIT_BLACKLIST', ''), // Comma-separated IPs
        'global_limit' => 1000, // requests per hour
    ],

    // Response headers
    'headers' => [
        'enabled' => true,
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'retry_after' => 'X-RateLimit-Retry-After',
    ],

    // Custom messages
    'messages' => [
        'too_many_attempts' => 'Too many attempts. Please try again later.',
        'ip_blocked' => 'Your IP address has been temporarily blocked due to suspicious activity.',
        'auth_blocked' => 'Too many failed login attempts. Please try again after :minutes minutes.',
    ],
];