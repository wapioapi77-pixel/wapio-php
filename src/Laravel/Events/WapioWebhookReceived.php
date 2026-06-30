<?php

declare(strict_types=1);

namespace Wapio\Laravel\Events;

final readonly class WapioWebhookReceived
{
    /**
     * @param array<string,mixed> $event
     */
    public function __construct(public array $event)
    {
    }

    public function type(): ?string
    {
        $type = $this->event['type'] ?? $this->event['event'] ?? null;

        return is_string($type) ? $type : null;
    }
}
