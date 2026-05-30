<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait MiscTrait
{
    /** @param array<string,mixed> $options */
    public function onWhatsapp(string $phone, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/on-whatsapp/' . rawurlencode($phone), options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getUser(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/user', options: $options);
    }
}
