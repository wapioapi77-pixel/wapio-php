<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait SessionsTrait
{
    /** @param array{status?:string|array<int,string>,limit?:int,before_id?:string} $params @param array<string,mixed> $options */
    public function listSessions(array $params = [], array $options = []): Response
    {
        $query = [];
        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }
        if (isset($params['before_id'])) {
            $query['before_id'] = $params['before_id'];
        }
        if (isset($params['status'])) {
            $query['status'] = is_array($params['status']) ? implode(',', $params['status']) : $params['status'];
        }
        return $this->http->request(method: 'GET', path: '/v1/whatsapp-sessions', preferToken: 'pat', query: $query, options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId), options: $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function createSession(array $payload, array $options = []): Response
    {
        $body = array_filter([
            'label' => $payload['label'] ?? null,
            'phone_number' => $payload['phone_number'] ?? null,
            'settings' => $payload['settings'] ?? null,
        ], static fn ($value) => $value !== null);

        return $this->http->request(method: 'POST', path: '/v1/whatsapp-sessions', patOnly: true, body: $body, options: $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function updateSession(string $sessionId, array $payload, array $options = []): Response
    {
        $body = array_filter([
            'label' => $payload['label'] ?? null,
            'phone_number' => $payload['phone_number'] ?? null,
            'settings' => $payload['settings'] ?? null,
        ], static fn ($value) => $value !== null);

        return $this->http->request(method: 'PATCH', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId), body: $body, options: $options);
    }

    /** @param array<string,mixed> $options */
    public function deleteSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'DELETE', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId), patOnly: true, options: $options);
    }

    /** @param array<string,mixed> $options */
    public function connectSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/connect', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function disconnectSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/disconnect', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getSessionQrCode(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/qrcode', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function regenerateApiKey(string $sessionId, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/regenerate-key', patOnly: true, options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getSessionStatus(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/status', options: $options);
    }
}
