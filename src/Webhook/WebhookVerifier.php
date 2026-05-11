<?php

declare(strict_types=1);

namespace Wapio\Webhook;

use Wapio\Exceptions\WapioWebhookException;

/**
 * Wapio webhook signature verifier.
 *
 * The server signs every delivery with::
 *
 *     X-Webhook-Signature: t=<unix>,v1=<hex-hmac>
 *
 * where the HMAC is HMAC-SHA256(signing_secret, "<timestamp>.<body>").
 * The verifier rejects signatures older than 300 seconds by default to
 * bound replay attacks (matches the server tolerance).
 */
final class WebhookVerifier
{
    public const SIGNATURE_HEADER = 'X-Webhook-Signature';
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Constant-time-verify a Wapio webhook signature.
     *
     * Always verify against the raw request body bytes — NOT against
     * `json_decode`'d input. Many frameworks re-serialize JSON with
     * different whitespace, breaking the signature.
     *
     * @param ?callable():int $now Optional clock override for tests.
     */
    public static function verify(
        string $signingSecret,
        ?string $signatureHeader,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?callable $now = null,
    ): bool {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }
        if (!preg_match('/t=(\d+)/', $signatureHeader, $tMatch)) {
            return false;
        }
        if (!preg_match('/v1=([a-f0-9]+)/', $signatureHeader, $vMatch)) {
            return false;
        }
        $timestamp = (int) $tMatch[1];
        $v1 = $vMatch[1];
        // $v1 is guaranteed non-empty by the regex (`v1=([a-f0-9]+)`).
        if ($timestamp <= 0) {
            return false;
        }
        $currentTime = $now !== null ? (int) $now() : time();
        if (abs($currentTime - $timestamp) > $toleranceSeconds) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $signingSecret);
        return hash_equals($expected, $v1);
    }

    /**
     * Verify + parse a Wapio webhook delivery in one call.
     *
     * Throws WapioWebhookException (statusCode=401) on bad signature or
     * expired timestamp, or (statusCode=400) on invalid JSON.
     *
     * @param ?callable():int $now
     */
    public static function handle(
        string $signingSecret,
        ?string $signatureHeader,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?callable $now = null,
    ): WebhookEvent {
        if (!self::verify($signingSecret, $signatureHeader, $rawBody, $toleranceSeconds, $now)) {
            throw new WapioWebhookException('Invalid Wapio webhook signature.', 401);
        }
        $parsed = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WapioWebhookException(
                'Webhook body was not valid JSON: ' . json_last_error_msg(),
                400,
            );
        }
        if (!is_array($parsed) || array_is_list($parsed)) {
            throw new WapioWebhookException('Webhook body must be a JSON object.', 400);
        }
        $payload = $parsed['payload'] ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }
        return new WebhookEvent(
            eventType: (string) ($parsed['event_type'] ?? ''),
            accountId: (string) ($parsed['account_id'] ?? ''),
            sessionId: (string) ($parsed['session_id'] ?? ''),
            emittedAt: $parsed['emitted_at'] ?? 0,
            payload: $payload,
            raw: $parsed,
        );
    }
}
