# wapio/wapio

Official PHP / Laravel SDK for [Wapio](https://www.wapio.io) — a developer-first WhatsApp API. Send messages, manage WhatsApp sessions, verify webhooks. Works in any PHP 8.1+ project and auto-registers a Laravel service provider + facade when Laravel is present.

```sh
composer require wapio/wapio
```

> PHP 8.1+. Single HTTP dep is Guzzle 7. Framework-agnostic core — Laravel is optional.

## Quickstart (any PHP project)

```php
<?php
use Wapio\Wapio;

$wapio = new Wapio(apiKey: 'bps_sk_…');   // session-scoped key

$result = $wapio->sendText([
    'to' => '+15551234567',
    'text' => 'Hello from Wapio 👋',
]);

echo $result->data['msgId'];                // bps_msg_…
echo $result->rateLimit->remaining;         // 254
```

## Laravel

The package self-registers via Composer's auto-discovery (`extra.laravel.providers`). No manual provider entry needed.

```sh
composer require wapio/wapio
php artisan vendor:publish --tag=wapio-config
```

`.env`:

```env
WAPIO_API_KEY=bps_sk_...
WAPIO_PAT=bps_pat_...                  # for lifecycle ops
WAPIO_WEBHOOK_SECRET=whsec_...
# optional:
# WAPIO_BASE_URL=https://api.wapio.io
# WAPIO_WEBHOOK_ROUTE=/wapio/webhook
```

Use via dependency injection:

```php
use Wapio\Wapio;

class OrderShippedController
{
    public function __invoke(Wapio $wapio): void
    {
        $wapio->sendText([
            'to' => $this->customerPhone,
            'text' => 'Your order has shipped 📦',
        ]);
    }
}
```

Or via the facade (root-namespace alias is `WapioApi`):

```php
use WapioApi;

WapioApi::sendText(['to' => '+15551234567', 'text' => 'Hi']);
```

Or import the facade FQN:

```php
use Wapio\Laravel\Facades\Wapio;

Wapio::sendText(['to' => '+15551234567', 'text' => 'Hi']);
```

## Authentication

| Token | Format | Best for |
|---|---|---|
| **Personal Access Token (PAT)** | `bps_pat_…` | Account-level routes: create/delete sessions, dashboard, rotate keys, change webhook/proxy. |
| **Session-scoped key** | `bps_sk_…` | Send/read on a single bound session. Cannot rotate itself, delete its session, or change webhook/proxy. |

```php
$wapio = new Wapio(
    apiKey: 'bps_sk_…',
    personalAccessToken: 'bps_pat_…',
);
```

If you call a PAT-only method (`createSession`, `deleteSession`, `regenerateApiKey`, `getDashboardOverview`, webhook/proxy upsert/delete) without a PAT, the SDK throws `WapioConfigException` **before** any HTTP round-trip.

## Sending messages

```php
$wapio->sendText(['to' => '+15551234567', 'text' => 'Hi!']);

$wapio->sendImage(['to' => '+15551234567', 'imageUrl' => 'https://example.com/cat.jpg', 'caption' => '🐈']);
$wapio->sendVideo(['to' => '+15551234567', 'videoUrl' => 'https://example.com/clip.mp4']);
$wapio->sendAudio(['to' => '+15551234567', 'audioUrl' => 'https://example.com/voice.ogg']);
$wapio->sendDocument(['to' => '+15551234567', 'documentUrl' => 'https://example.com/invoice.pdf', 'file_name' => 'invoice.pdf']);
$wapio->sendSticker(['to' => '+15551234567', 'stickerUrl' => 'https://example.com/x.webp']);

$wapio->sendLocation(['to' => '+15551234567', 'location' => ['latitude' => 37.422, 'longitude' => -122.084, 'name' => 'Googleplex']]);
$wapio->sendPoll(['to' => '+15551234567', 'poll' => ['question' => 'Tea or coffee?', 'options' => ['Tea', 'Coffee']]]);

// Quote + mentions (also available as descriptive helper methods)
$wapio->sendMessageWithMentions([
    'to' => '<groupJid>@g.us',
    'text' => '@1555 hello',
    'mentions' => ['15551234567@s.whatsapp.net'],
]);
$wapio->sendQuotedMessage([
    'to' => '+15551234567',
    'text' => 'Replying to your earlier message',
    'quoted_message_id' => 'bps_msg_previous',
]);

// View-once image
$wapio->sendImage(['to' => '+15551234567', 'imageUrl' => 'https://...', 'viewOnce' => true]);
```

### Idempotency

`send*` methods auto-attach a UUID4 `Idempotency-Key`. Pass your own for cross-process dedup:

```php
$wapio->sendText(
    ['to' => '+15551234567', 'text' => 'Order #42 shipped'],
    ['idempotency_key' => 'order:42:shipped']
);
```

### Message lifecycle

```php
$wapio->getMessageInfo('bps_msg_…');
$wapio->editMessage('bps_msg_…', 'Corrected text');
$wapio->deleteMessage('bps_msg_…');
$wapio->resendMessage('bps_msg_…');
$wapio->markMessagesRead(['bps_msg_a', 'bps_msg_b'], '15551234567@s.whatsapp.net');
```

## Sessions

```php
// Create (PAT only). Response includes the one-time raw bps_sk_*.
$created = $wapio->createSession([
    'label' => 'Support line',
    'webhook' => [
        'url' => 'https://example.com/wapio/webhook',
        'events' => ['message_received', 'message_sent', 'qrcode_updated'],
        'signing_secret' => 'whsec_...',
    ],
]);
$rawSessionKey = $created->data['session_api_key']['raw'] ?? null;  // store this once!

// List, get, update, delete
$wapio->listSessions(['status' => 'connected', 'limit' => 50]);
$wapio->getSession('bps_sess_ws_…');
$wapio->updateSession('bps_sess_ws_…', ['label' => 'Support — EU']);
$wapio->deleteSession('bps_sess_ws_…');

// Pairing flow
$wapio->getSessionQrCode('bps_sess_ws_…');
$wapio->disconnectSession('bps_sess_ws_…');
$wapio->connectSession('bps_sess_ws_…');
$wapio->restartSession('bps_sess_ws_…');

// Rotate the session-scoped key (PAT only)
$rotated = $wapio->regenerateApiKey('bps_sess_ws_…');
echo $rotated->data['session_api_key']['raw'];   // store this once!
```

### Method aliases

Every short method ships a longer descriptive alias so you can use whichever reads better in your codebase:

| Long form | Short form |
|---|---|
| `getAllWhatsAppSessions()` | `listSessions()` |
| `getWhatsAppSessionDetails($id)` | `getSession($id)` |
| `createWhatsAppSession([...])` | `createSession([...])` |
| `updateWhatsAppSession($id, [...])` | `updateSession($id, [...])` |
| `deleteWhatsAppSession($id)` | `deleteSession($id)` |
| `connectWhatsAppSession($id)` | `connectSession($id)` |
| `disconnectWhatsAppSession($id)` | `disconnectSession($id)` |
| `getWhatsAppSessionQrCode($id)` | `getSessionQrCode($id)` |
| `getSessionUserInfo()` | `getUser()` |
| `getContactInfo($phone)` | `getContact($phone)` |
| `uploadMediaFile([...])` | `uploadMediaGrant([...])` / `directUpload([...])` |
| `decryptMediaFile([...])` | `decryptMedia([...])` |
| `sendMessageWithMentions([...])` | `sendText([...])` (with `mentions`) |
| `sendQuotedMessage([...])` | `sendText([...])` (with `quoted_message_id`) |

## Webhooks

Wapio signs every delivery with `X-Webhook-Signature: t=<unix>,v1=<hex-hmac>` where the HMAC is `HMAC-SHA256(signing_secret, "<timestamp>.<body>")`. Default replay-window is 300s (matches the server). Verification uses `hash_equals()` for a constant-time compare — **never** roll your own `==` check against the header.

### Laravel (auto-mounted)

The service provider auto-mounts `POST /wapio/webhook` (configurable). The default controller verifies the signature, parses the event, and fires a `WapioWebhookReceived` Laravel event. Listen for it:

```php
use Wapio\Laravel\Events\WapioWebhookReceived;
use Illuminate\Support\Facades\Event;

Event::listen(function (WapioWebhookReceived $e) {
    match ($e->event->eventType) {
        'message_received' => /* … */,
        'message_sent' => /* … */,
        'qrcode_updated' => /* … */,
        default => null,
    };
});
```

Disable the auto-route and mount your own:

```env
WAPIO_WEBHOOK_ROUTE=
```

```php
use Wapio\Laravel\Http\Controllers\WebhookController;
use Wapio\Laravel\Http\Middleware\VerifyWapioWebhook;

Route::post('/my/wapio/path', WebhookController::class);
// or:
Route::post('/my/wapio/path', MyHandler::class)->middleware(VerifyWapioWebhook::class);
```

### Vanilla PHP

```php
use Wapio\Webhook\WebhookVerifier;
use Wapio\Exceptions\WapioWebhookException;

try {
    $event = WebhookVerifier::handle(
        signingSecret: $_ENV['WAPIO_WEBHOOK_SECRET'],
        signatureHeader: $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null,
        rawBody: file_get_contents('php://input'),
    );
    // $event->eventType, $event->sessionId, $event->payload
    http_response_code(200);
} catch (WapioWebhookException $e) {
    http_response_code($e->getStatusCode());   // 401 bad sig, 400 bad JSON
}
```

## Errors

```php
use Wapio\Exceptions\WapioApiException;

try {
    $wapio->sendText(['to' => '+1', 'text' => 'Hi']);
} catch (WapioApiException $e) {
    $e->statusCode;       // 422
    $e->reasonCode;       // "validation_failed"
    $e->apiMessage;       // human-readable
    $e->errorDetails;     // structured field errors, if any
    $e->requestId;        // server correlation id for support
    $e->rateLimit;        // RateLimitInfo
}
```

## Retry policy

Retries on **429** (honoring `Retry-After`) and **5xx** with exponential backoff + jitter (default 3 retries, 0.5s → 10s cap). Tune via `RetryConfig`:

```php
use Wapio\Http\RetryConfig;

$wapio = new Wapio(
    apiKey: 'bps_sk_…',
    retry: new RetryConfig(enabled: true, maxRetries: 5, initialBackoff: 1.0, maxBackoff: 30.0),
);
```

## License

[MIT](./LICENSE)
