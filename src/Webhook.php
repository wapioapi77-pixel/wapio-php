<?php

declare(strict_types=1);

namespace Wapio;

use JsonException;
use Wapio\Exceptions\WebhookVerificationException;
use Wapio\Laravel\Events\WapioWebhookReceived;

final class Webhook
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    public static function computeSignature(string $secret, int|string $timestamp, string $rawBody): string
    {
        return hash_hmac('sha256', (string) $timestamp . '.' . $rawBody, $secret);
    }

    /**
     * @return array{timestamp:int,signature:string}
     */
    public static function parseSignatureHeader(string $header): array
    {
        $timestamp = null;
        $signature = null;

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't' && $value !== null && ctype_digit($value)) {
                $timestamp = (int) $value;
            }
            if ($key === 'v1' && $value !== null && $value !== '') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null) {
            throw new WebhookVerificationException('Invalid Wapio webhook signature header.');
        }

        return ['timestamp' => $timestamp, 'signature' => $signature];
    }

    /**
     * @throws WebhookVerificationException
     */
    public static function verifySignature(
        string $secret,
        string $signatureHeader,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        $parsed = self::parseSignatureHeader($signatureHeader);
        $clock = $now ?? time();

        if ($toleranceSeconds > 0 && abs($clock - $parsed['timestamp']) > $toleranceSeconds) {
            throw new WebhookVerificationException('Wapio webhook timestamp is outside the allowed tolerance.');
        }

        $expected = self::computeSignature($secret, $parsed['timestamp'], $rawBody);
        if (!hash_equals($expected, $parsed['signature'])) {
            throw new WebhookVerificationException('Invalid Wapio webhook signature.');
        }

        return true;
    }

    /**
     * Verify and parse a webhook payload.
     *
     * @return array<string,mixed>
     *
     * @throws JsonException
     * @throws WebhookVerificationException
     */
    public static function constructEvent(
        string $secret,
        string $signatureHeader,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): array {
        self::verifySignature($secret, $signatureHeader, $rawBody, $toleranceSeconds);

        return self::parseEvent($rawBody);
    }

    /**
     * @return array<string,mixed>
     *
     * @throws JsonException
     */
    public static function parseEvent(string|array $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        /** @var array<string,mixed> $event */
        $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return $event;
    }

    /**
     * Build the Laravel event object and optionally dispatch it.
     *
     * Pass Laravel's `event(...)` helper, `Event::dispatch(...)`, or any
     * callable dispatcher when you want the SDK to emit the event.
     *
     * @param array<string,mixed> $event
     */
    public static function dispatchLaravelEvent(array $event, ?callable $dispatcher = null): WapioWebhookReceived
    {
        $laravelEvent = new WapioWebhookReceived($event);

        if ($dispatcher !== null) {
            $dispatcher($laravelEvent);
        }

        return $laravelEvent;
    }
}
