<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Product Mapping
    |--------------------------------------------------------------------------
    |
    | This configuration maps subscription tiers and billing periods to their
    | corresponding Stripe price IDs. These should be updated with your actual
    | Stripe price IDs from your Stripe dashboard.
    |
    */

    'products' => [
        // Note: Silver tier has been retired. Bronze/Silver picks are now "Free" tier.
        'gold' => [
            'name' => 'Gold',
            'monthly' => env('GOLD_MONTHLY'),
            'weekly' => env('GOLD_WEEKLY'),
            'daily' => env('GOLD_DAILY'),
            'features' => [
                'All Free picks included',
                'Access to Gold-tier premium picks',
                'Priority email notifications',
                'Advanced analytics',
                'Early access to picks',
            ],
        ],
        'platinum' => [
            'name' => 'Platinum',
            'monthly' => env('PLATINUM_MONTHLY'),
            'weekly' => env('PLATINUM_WEEKLY'),
            'daily' => env('PLATINUM_DAILY'),
            'features' => [
                'All Free and Gold picks included',
                'Access to ALL premium picks',
                'Instant notifications',
                'Premium analytics',
                'VIP support',
                'Exclusive insider tips',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Price ID Mapping
    |--------------------------------------------------------------------------
    |
    | Direct mapping of price IDs to their tier and period. This is used
    | to determine the user's current tier from their Stripe subscription.
    |
    */

    'price_to_tier' => [
        // Note: Silver tier mappings kept for backward compatibility with existing subscriptions
        env('SILVER_MONTHLY') => ['tier' => 'Silver', 'period' => 'monthly'],
        env('SILVER_WEEKLY') => ['tier' => 'Silver', 'period' => 'weekly'],
        env('SILVER_DAILY') => ['tier' => 'Silver', 'period' => 'daily'],
        env('GOLD_MONTHLY') => ['tier' => 'Gold', 'period' => 'monthly'],
        env('GOLD_WEEKLY') => ['tier' => 'Gold', 'period' => 'weekly'],
        env('GOLD_DAILY') => ['tier' => 'Gold', 'period' => 'daily'],
        env('PLATINUM_MONTHLY') => ['tier' => 'Platinum', 'period' => 'monthly'],
        env('PLATINUM_WEEKLY') => ['tier' => 'Platinum', 'period' => 'weekly'],
        env('PLATINUM_DAILY') => ['tier' => 'Platinum', 'period' => 'daily'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'trial_days' => env('STRIPE_TRIAL_DAYS', 0),
    'collect_billing_address' => env('STRIPE_COLLECT_BILLING_ADDRESS', false),
];
