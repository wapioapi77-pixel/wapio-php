<?php

declare(strict_types=1);

namespace Wapio\Webhook;

final readonly class WebhookEvent
{
    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $eventType,
        public string $accountId,
        public string $sessionId,
        public int|float|string $emittedAt,
        public array $payload,
        public array $raw,
    ) {
    }
}
