<?php

declare(strict_types=1);

namespace Wapio\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Routing\Controller;
use Wapio\Exceptions\WapioWebhookException;
use Wapio\Laravel\Events\WapioWebhookReceived;
use Wapio\Webhook\WebhookVerifier;

/**
 * Auto-mounted at `config('wapio.webhook_route')` by the
 * `routes/webhooks.php` file loaded from the service provider.
 *
 * Reads the raw body, verifies the HMAC-SHA256 signature, and dispatches
 * a `WapioWebhookReceived` Laravel event with the parsed `WebhookEvent`.
 * Listeners do the actual business logic.
 *
 * Returns 200 on success, 401 on bad signature, 400 on invalid JSON.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): LaravelResponse
    {
        $secret = (string) config('wapio.webhook_secret', '');
        if ($secret === '') {
            return new LaravelResponse('Wapio webhook secret is not configured.', 500);
        }

        $rawBody = $request->getContent();
        $signatureHeader = $request->headers->get(WebhookVerifier::SIGNATURE_HEADER);

        try {
            $event = WebhookVerifier::handle(
                signingSecret: $secret,
                signatureHeader: $signatureHeader,
                rawBody: $rawBody,
            );
        } catch (WapioWebhookException $e) {
            return new LaravelResponse(null, $e->getStatusCode());
        }

        event(new WapioWebhookReceived($event));

        return new LaravelResponse(null, 200);
    }
}
