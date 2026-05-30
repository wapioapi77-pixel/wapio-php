<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait MediaTrait
{
    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function decryptMedia(array $payload, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/api/decrypt-media', body: $payload, options: $options);
    }
}
