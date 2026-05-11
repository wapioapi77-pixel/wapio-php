<?php

declare(strict_types=1);

namespace Wapio\Http;

/**
 * Envelope returned by every SDK call.
 *
 * @template T
 */
final readonly class Response
{
    /** @param T $data */
    public function __construct(
        public mixed $data,
        public RateLimitInfo $rateLimit,
        public ?string $requestId,
        public int $statusCode,
    ) {
    }
}
