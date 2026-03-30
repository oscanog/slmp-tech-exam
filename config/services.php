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

    'runtime_check' => [
        'base_url' => env('SLMP_RUNTIME_CHECK_BASE_URL', 'http://localhost:8080'),
        'timeout' => (int) env('SLMP_RUNTIME_CHECK_TIMEOUT', 10),
        'expected_counts' => [
            'users' => (int) env('SLMP_RUNTIME_CHECK_USERS', 10),
            'posts' => (int) env('SLMP_RUNTIME_CHECK_POSTS', 100),
            'comments' => (int) env('SLMP_RUNTIME_CHECK_COMMENTS', 500),
            'albums' => (int) env('SLMP_RUNTIME_CHECK_ALBUMS', 100),
            'photos' => (int) env('SLMP_RUNTIME_CHECK_PHOTOS', 5000),
            'todos' => (int) env('SLMP_RUNTIME_CHECK_TODOS', 200),
        ],
    ],

];
