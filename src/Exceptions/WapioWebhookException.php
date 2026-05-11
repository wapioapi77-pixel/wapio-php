<?php

declare(strict_types=1);

namespace Wapio\Exceptions;

use Exception;

class WapioWebhookException extends Exception
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
