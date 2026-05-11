<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait ContactsTrait
{
    /** @param array<string,mixed> $options */
    public function getContacts(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/contacts', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getContact(string $phone, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/contacts/' . rawurlencode($phone),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getContactProfilePicture(string $jid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/contacts/' . rawurlencode($jid) . '/picture',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function blockContact(string $phone, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/contacts/' . rawurlencode($phone) . '/block',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function unblockContact(string $phone, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/contacts/' . rawurlencode($phone) . '/unblock',
            options: $options,
        );
    }

    /**
     * @param array{phone:string,name?:string} $payload
     * @param array<string,mixed> $options
     */
    public function upsertContact(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'PUT',
            path: '/api/contacts',
            body: $payload,
            options: $options,
        );
    }
}
