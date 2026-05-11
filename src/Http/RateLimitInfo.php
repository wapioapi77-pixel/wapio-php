<?php

declare(strict_types=1);

namespace Wapio\Http;

/**
 * Rate-limit metadata extracted from response headers.
 *
 * All fields may be null if the server did not return that header for the
 * request (e.g., on routes the limiter does not track).
 */
final readonly class RateLimitInfo
{
    public function __construct(
        public ?int $limit = null,
        public ?int $remaining = null,
        public ?int $resetTimestamp = null,
        public ?int $retryAfterSeconds = null,
    ) {
    }
}
