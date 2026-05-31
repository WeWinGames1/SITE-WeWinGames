<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quick Checkout Feature
    |--------------------------------------------------------------------------
    |
    | Enable the payment-first registration flow. When enabled, the "Sign Up"
    | buttons will route to /quick-checkout instead of /register.
    |
    */
    'quick_checkout_enabled' => env('QUICK_CHECKOUT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Affiliate Trial Feature
    |--------------------------------------------------------------------------
    |
    | Enable the 7-day trial checkout flow for affiliate sales emails.
    | This provides a separate checkout URL (/affiliate-trial) that offers
    | a 7-day free trial before converting to monthly billing.
    |
    */
    'affiliate_trial_enabled' => env('AFFILIATE_TRIAL_ENABLED', false),
];
