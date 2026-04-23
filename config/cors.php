<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | Allows the React frontend to call the Laravel API.
    | All /api/* and Sanctum routes are covered.
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/auth',
        'login',
        'logout',
        'register',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];
