<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'marketing_enabled' => env('SENDGRID_MARKETING_ENABLED', false),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'turnstile' => [
        'enabled' => filter_var(env('TURNSTILE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    'cloudflare' => [
        'enabled' => env('CLOUDFLARE_ENABLED', false),
        'trusted_proxies' => env('CLOUDFLARE_TRUSTED_PROXIES', false),
        'blocked_countries' => explode(',', env('CLOUDFLARE_BLOCKED_COUNTRIES', '')),
        'security_enabled' => env('CLOUDFLARE_SECURITY_ENABLED', false),
    ],

    'reddit' => [
        'pixel_id' => env('REDDIT_PIXEL_ID'),
        'conversion_token' => env('REDDIT_CONVERSION_TOKEN'),
    ],

    // X (Twitter) Ads attribution + conversion tracking
    'twitter' => [
        'pixel_id' => env('TWITTER_PIXEL_ID'),
        'events' => [
            // Event IDs (tw-XXXX-YYYY) from the X Ads Events Manager, one per conversion.
            'content_view' => env('TWITTER_EVENT_CONTENT_VIEW'),
            'checkout_initiated' => env('TWITTER_EVENT_CHECKOUT_INITIATED'),
            'signup' => env('TWITTER_EVENT_SIGNUP'),
            'purchase' => env('TWITTER_EVENT_PURCHASE'),
        ],
        // Conversion API (server-side). Token must stay server-side only — never
        // expose it to the browser / Inertia env or commit the real value.
        'conversion_token' => env('TWITTER_CONVERSION_TOKEN'),
        'api_version' => env('TWITTER_API_VERSION', '12'),
    ],

    // Heatmap / session recording slot. Currently supports Microsoft Clarity.
    'heatmap' => [
        'provider' => env('HEATMAP_PROVIDER', 'clarity'),
        'id' => env('HEATMAP_ID'),
    ],

    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'public_key' => env('DISCORD_PUBLIC_KEY'),
        'redirect' => env('DISCORD_REDIRECT_URI'),

        // Bot configuration
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'invite_url' => env('DISCORD_INVITE_URL'),

        // Role IDs mapped to subscription tiers (hierarchical: higher tiers include lower tier roles)
        'roles' => [
            'free' => env('DISCORD_ROLE_FREE'),
            'gold' => env('DISCORD_ROLE_GOLD'),
            'platinum' => env('DISCORD_ROLE_PLATINUM'),
        ],
    ],

    // SpringBig API
    // Staging: https://gamma.api.springbig.technology/pos/v1
    // Production: https://production.api.springbig.technology/pos/v1
    'springbig' => [
        'enabled' => env('SPRINGBIG_ENABLED', false),
        'base_url' => env('SPRINGBIG_BASE_URL', 'https://production.api.springbig.technology/pos/v1'),
        'api_key' => env('SPRINGBIG_API_KEY'),
        'auth_token' => env('SPRINGBIG_AUTH_TOKEN'),

        // External Group API (different base URL)
        // Uses segments to manage tier-based recipients
        'external_group_enabled' => env('SPRINGBIG_EXTERNAL_GROUP_ENABLED', false),
        'external_group_base_url' => env('SPRINGBIG_EXTERNAL_GROUP_BASE_URL', 'https://production.api.springbig.technology/general/v1'),
        'external_group_id' => env('SPRINGBIG_EXTERNAL_GROUP_ID'), // Created group ID

        // Segment IDs for each tier (created via createSegments or manually)
        'segment_ids' => [
            'free' => env('SPRINGBIG_SEGMENT_FREE'),
            'silver' => env('SPRINGBIG_SEGMENT_SILVER'),
            'gold' => env('SPRINGBIG_SEGMENT_GOLD'),
            'platinum' => env('SPRINGBIG_SEGMENT_PLATINUM'),
            'canceled' => env('SPRINGBIG_SEGMENT_CANCELED'),
        ],
    ],

];
