<?php

declare(strict_types=1);

namespace Wapio\Exceptions;

use Exception;
use Wapio\Http\RateLimitInfo;

/**
 * Raised when the Wapio API returns a non-2xx response.
 */
class WapioApiException extends Exception
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $apiMessage,
        public readonly ?string $reasonCode = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $errorDetails = null,
        public readonly ?string $requestId = null,
        public readonly ?RateLimitInfo $rateLimit = null,
    ) {
        parent::__construct(
            sprintf('Wapio API error (%d): %s', $statusCode, $apiMessage),
            $statusCode,
        );
    }
}
