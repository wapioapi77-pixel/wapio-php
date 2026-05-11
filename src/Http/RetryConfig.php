<?php

declare(strict_types=1);

namespace Wapio\Http;

final readonly class RetryConfig
{
    public function __construct(
        public bool $enabled = true,
        public int $maxRetries = 3,
        public float $initialBackoff = 0.5,
        public float $maxBackoff = 10.0,
    ) {
    }
}
