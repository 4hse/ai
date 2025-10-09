<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 4HSE API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to 4HSE service API.
    |
    */

    'api' => [
        'base_url' => env('FOURHSE_API_URL', 'https://service.4hse.local'),
        'timeout' => env('FOURHSE_API_TIMEOUT', 30),
        'retry_times' => env('FOURHSE_API_RETRY_TIMES', 3),
        'retry_delay' => env('FOURHSE_API_RETRY_DELAY', 1000), // milliseconds
        'verify_ssl' => env('FOURHSE_API_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Account Token (Fallback)
    |--------------------------------------------------------------------------
    |
    | Optional service account token for server-to-server communication.
    | Not recommended for production use with MCP (use OAuth2 instead).
    |
    */
    'service_token' => env('FOURHSE_SERVICE_TOKEN'),
];
