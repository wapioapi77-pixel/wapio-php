<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Wapio\Laravel\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Wapio webhook route
|--------------------------------------------------------------------------
|
| Auto-loaded by `Wapio\Laravel\WapioServiceProvider`. Mounts at the path
| configured in `config('wapio.webhook_route')` (default `/wapio/webhook`).
|
| Disable by setting `WAPIO_WEBHOOK_ROUTE=` (empty string) in `.env`, or
| `'webhook_route' => null` in `config/wapio.php`, then mount your own
| route to `WebhookController::class` (the controller is reusable on any
| path you choose).
*/

$path = config('wapio.webhook_route', '/wapio/webhook');

if (is_string($path) && $path !== '') {
    Route::post($path, WebhookController::class)
        ->middleware(config('wapio.webhook_middleware', []))
        ->name('wapio.webhook');
}
