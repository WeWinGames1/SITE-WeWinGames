<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Services Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Google services including
    | Analytics and Tag Manager.
    |
    */

    'analytics' => [
        'tag_id' => env('GOOGLE_ANALYTICS_TAG_ID'),
    ],

    'tag_manager' => [
        'container_id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],
];
