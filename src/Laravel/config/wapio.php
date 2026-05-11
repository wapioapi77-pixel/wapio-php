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
    | operations: create/delete sessions, dashboard, rotate keys,
    | change webhook/proxy configuration.
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
    | Webhook signing secret (whsec_*). Used by the auto-mounted
    | WebhookController and the VerifyWapioWebhook middleware to
    | authenticate incoming events via HMAC-SHA256.
    */
    'webhook_secret' => env('WAPIO_WEBHOOK_SECRET'),

    /*
    | Path the default webhook controller listens on. Set to empty
    | string to disable the auto-mounted route entirely and define
    | your own route pointing at:
    |     Wapio\Laravel\Http\Controllers\WebhookController
    */
    'webhook_route' => env('WAPIO_WEBHOOK_ROUTE', '/wapio/webhook'),

    /*
    | Middleware applied to the auto-mounted webhook route. Default
    | is empty — the route authenticates via HMAC signature, not
    | session, so CSRF / session middleware would just break it.
    */
    'webhook_middleware' => [],

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
