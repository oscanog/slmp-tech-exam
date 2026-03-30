<?php

return [
    'jsonplaceholder' => [
        'base_url' => env('JSONPLACEHOLDER_BASE_URL', 'https://jsonplaceholder.typicode.com'),
        'timeout' => (int) env('JSONPLACEHOLDER_TIMEOUT', 15),
        'retry_times' => (int) env('JSONPLACEHOLDER_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('JSONPLACEHOLDER_RETRY_SLEEP_MS', 500),
    ],
];
