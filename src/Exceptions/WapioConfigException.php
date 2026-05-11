<?php

declare(strict_types=1);

namespace Wapio\Exceptions;

use Exception;

/**
 * Raised before any network call when the SDK is configured incorrectly.
 *
 * Common case: a PAT-only route is invoked but only an apiKey is set, so
 * the SDK fails fast rather than making the round-trip.
 */
class WapioConfigException extends Exception
{
}
