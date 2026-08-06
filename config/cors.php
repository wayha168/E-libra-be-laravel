<?php

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Tuned for Flutter (mobile + web) and other API clients using Bearer tokens.
    | Mobile Flutter does not need CORS; Flutter web and browsers do.
    |
    | Keep supports_credentials=false when using allowed_origins=* (Bearer auth).
    | For cookie/SPA auth, set CORS_ALLOWED_ORIGINS to explicit domains and
    | CORS_SUPPORTS_CREDENTIALS=true.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'broadcasting/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins === [] ? ['*'] : $allowedOrigins,

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', ''))
    ))),

    'allowed_headers' => ['*'],

    // Useful for Flutter web PDF / file downloads
    'exposed_headers' => [
        'Authorization',
        'Content-Disposition',
        'Content-Type',
        'X-Requested-With',
    ],

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
