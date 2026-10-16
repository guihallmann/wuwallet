<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'brapi' => [
        'base_url' => env('BRAPI_BASE_URL', 'https://brapi.dev'),
        // Optional: without a key brapi allows 20 requests/minute per IP.
        'key' => env('BRAPI_API_KEY'),
        'timeout' => (int) env('BRAPI_TIMEOUT', 15),
        'connect_timeout' => (int) env('BRAPI_CONNECT_TIMEOUT', 5),
        'max_attempts' => (int) env('BRAPI_MAX_ATTEMPTS', 4),
        'page_size' => 2000,
        'max_pages' => 10,
        'page_delay_ms' => 3500,
        'max_retry_after_seconds' => 120,
    ],

];
