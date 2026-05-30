<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Wapio SDK configuration
|--------------------------------------------------------------------------
|
| Published by the Wapio service provider:
|     php artisan vendor:publish --tag=wapio-config
|
*/

return [
    /*
    | Session-scoped Bearer key (bps_sk_*). Bound to one WhatsApp session.
    | Use for send/read paths.
    */
    'api_key' => env('WAPIO_API_KEY'),

    /*
    | Personal Access Token (bps_pat_*). Required for account-level
    | operations: create/delete sessions and rotate keys.
    */
    'personal_access_token' => env('WAPIO_PAT'),

    /*
    | API base URL. Defaults to the public Wapio API.
    */
    'base_url' => env('WAPIO_BASE_URL', 'https://api.wapio.io'),

    /*
    | Per-request HTTP timeout in seconds.
    */
    'timeout' => (float) env('WAPIO_TIMEOUT', 60),

    /*
    | Retry policy. Tune in env or override in code via the
    | RetryConfig value object.
    */
    'retry' => [
        'enabled' => (bool) env('WAPIO_RETRY_ENABLED', true),
        'max_retries' => (int) env('WAPIO_RETRY_MAX', 3),
        'initial_backoff' => (float) env('WAPIO_RETRY_INITIAL_BACKOFF', 0.5),
        'max_backoff' => (float) env('WAPIO_RETRY_MAX_BACKOFF', 10.0),
    ],
];
