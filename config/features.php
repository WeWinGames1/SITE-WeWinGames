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
];
