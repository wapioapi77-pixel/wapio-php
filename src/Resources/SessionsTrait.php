<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

/**
 * Session lifecycle, settings, webhook/proxy config, and logs.
 */
trait SessionsTrait
{
    /**
     * List sessions visible to the caller.
     *
     * PATs see every non-deleted session in the account; session-scoped keys
     * see only the bound session.
     *
     * @param array{status?:string|array<int,string>,limit?:int,before_id?:string} $params
     * @param array<string,mixed> $options
     */
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
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions',
            preferToken: 'pat',
            query: $query,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId),
            options: $options,
        );
    }

    /**
     * Create a new WhatsApp session.
     *
     * PAT or dashboard-only. Response includes session_api_key.raw exactly
     * once — store it; the SDK cannot recover it.
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function createSession(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/whatsapp-sessions',
            patOnly: true,
            body: $payload,
            options: $options,
        );
    }

    /**
     * Update label, phone metadata, or operational toggles.
     *
     * Session-keys CANNOT pass `webhook` or `proxy` here.
     *
     * @param array{label?:string,phone_number?:string,settings?:array<string,bool>} $payload
     * @param array<string,mixed> $options
     */
    public function updateSession(string $sessionId, array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'PATCH',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId),
            body: $payload,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function deleteSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'DELETE',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId),
            patOnly: true,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function connectSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/connect',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function disconnectSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/disconnect',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function restartSession(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/restart',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getSessionQrCode(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/qrcode',
            options: $options,
        );
    }

    /**
     * Rotate the session-scoped Bearer key (bps_sk_*).
     *
     * PAT or dashboard-only — session-keys cannot rotate themselves.
     *
     * @param array<string,mixed> $options
     */
    public function regenerateApiKey(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/regenerate-key',
            patOnly: true,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getSessionStatus(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/status', options: $options);
    }

    // ---- settings ----------------------------------------------------------

    /** @param array<string,mixed> $options */
    public function getSessionSettings(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/settings',
            options: $options,
        );
    }

    /**
     * @param array<string,bool> $settings
     * @param array<string,mixed> $options
     */
    public function updateSessionSettings(string $sessionId, array $settings, array $options = []): Response
    {
        return $this->http->request(
            method: 'PATCH',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/settings',
            body: $settings,
            options: $options,
        );
    }

    // ---- webhook config ----------------------------------------------------

    /**
     * Set or replace this session's webhook destination. PAT or dashboard-only.
     *
     * @param array{target_url:string,subscribed_events:array<int,string>,signing_secret:string,is_active?:bool} $config
     * @param array<string,mixed> $options
     */
    public function upsertWebhookConfig(string $sessionId, array $config, array $options = []): Response
    {
        return $this->http->request(
            method: 'PUT',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/webhook',
            patOnly: true,
            body: $config,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getWebhookConfig(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/webhook',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function deleteWebhookConfig(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'DELETE',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/webhook',
            patOnly: true,
            options: $options,
        );
    }

    // ---- proxy config ------------------------------------------------------

    /**
     * @param array{scheme:string,host:string,port:int,username?:string|null,password?:string|null,is_active?:bool} $config
     * @param array<string,mixed> $options
     */
    public function upsertProxyConfig(string $sessionId, array $config, array $options = []): Response
    {
        return $this->http->request(
            method: 'PUT',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/proxy',
            patOnly: true,
            body: $config,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getProxyConfig(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/proxy',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function deleteProxyConfig(string $sessionId, array $options = []): Response
    {
        return $this->http->request(
            method: 'DELETE',
            path: '/v1/whatsapp-sessions/' . rawurlencode($sessionId) . '/proxy',
            patOnly: true,
            options: $options,
        );
    }

    // ---- logs / deliveries ------------------------------------------------

    /**
     * @param array{cursor?:string,limit?:int} $params
     * @param array<string,mixed> $options
     */
    public function listMessageLogs(string $sessionId, array $params = [], array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/whatsapp-sessions/' . rawurlencode($sessionId) . '/message-logs',
            query: $params,
            options: $options,
        );
    }

    /**
     * @param array{cursor?:string,limit?:int} $params
     * @param array<string,mixed> $options
     */
    public function listSessionLogs(string $sessionId, array $params = [], array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/whatsapp-sessions/' . rawurlencode($sessionId) . '/session-logs',
            query: $params,
            options: $options,
        );
    }

    /**
     * @param array{status?:string,cursor?:string,limit?:int} $params
     * @param array<string,mixed> $options
     */
    public function listWebhookDeliveries(string $sessionId, array $params = [], array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/whatsapp-sessions/' . rawurlencode($sessionId) . '/webhook-deliveries',
            query: $params,
            options: $options,
        );
    }
}
