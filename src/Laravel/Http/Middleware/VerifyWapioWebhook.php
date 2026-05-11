<?php

declare(strict_types=1);

namespace Wapio\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wapio\Webhook\WebhookVerifier;

/**
 * Standalone middleware that verifies the Wapio webhook HMAC-SHA256
 * signature on any route. Useful when you mount your own webhook
 * controller instead of using the auto-loaded one.
 *
 * Usage:
 *
 *     Route::post('/my-wapio-hook', MyHandler::class)
 *         ->middleware(VerifyWapioWebhook::class);
 */
class VerifyWapioWebhook
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = (string) config('wapio.webhook_secret', '');
        if ($secret === '') {
            return new Response('Wapio webhook secret is not configured.', 500);
        }

        $ok = WebhookVerifier::verify(
            signingSecret: $secret,
            signatureHeader: $request->headers->get(WebhookVerifier::SIGNATURE_HEADER),
            rawBody: $request->getContent(),
        );

        if (!$ok) {
            return new Response(null, 401);
        }

        return $next($request);
    }
}
