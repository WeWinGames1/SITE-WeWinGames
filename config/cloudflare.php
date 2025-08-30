<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudflare API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Cloudflare API credentials and zone information here.
    |
    */

    'enabled' => env('CLOUDFLARE_ENABLED', false),

    'email' => env('CLOUDFLARE_EMAIL'),

    'api_key' => env('CLOUDFLARE_API_KEY'),

    'zone_id' => env('CLOUDFLARE_ZONE_ID'),

    'api_url' => 'https://api.cloudflare.com/client/v4',
];
