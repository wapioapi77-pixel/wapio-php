<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait MessagesTrait
{
    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    private function sendPublic(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/send-message',
            idempotent: true,
            preferToken: 'session_key',
            body: array_filter($payload, static fn ($value) => $value !== null),
            options: $options,
        );
    }

    /** @param array{to?:string,channel_id?:string,text:string} $payload @param array<string,mixed> $options */
    public function sendText(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'text' => $payload['text'],
        ], $options);
    }

    /** @param array<string,mixed> $options */
    public function sendChannelMessage(string $channelId, string $text, array $options = []): Response
    {
        return $this->sendPublic(['channel_id' => $channelId, 'text' => $text], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendImage(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'kind' => 'image',
            'imageUrl' => $payload['imageUrl'] ?? null,
            'url' => $payload['url'] ?? null,
            'caption' => $payload['caption'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendVideo(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'kind' => 'video',
            'videoUrl' => $payload['videoUrl'] ?? null,
            'url' => $payload['url'] ?? null,
            'caption' => $payload['caption'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendDocument(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'kind' => 'document',
            'documentUrl' => $payload['documentUrl'] ?? null,
            'url' => $payload['url'] ?? null,
            'caption' => $payload['caption'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
            'file_name' => $payload['file_name'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $options */
    public function editMessage(string $messageId, string $text, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/messages/' . rawurlencode($messageId) . '/edit',
            preferToken: 'session_key',
            body: ['text' => $text],
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function deleteMessage(string $messageId, array $options = []): Response
    {
        return $this->http->request(
            method: 'DELETE',
            path: '/api/messages/' . rawurlencode($messageId),
            preferToken: 'session_key',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getMessageInfo(string $messageId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/messages/' . rawurlencode($messageId) . '/info',
            preferToken: 'session_key',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function checkIfOnWhatsapp(string $phone, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/on-whatsapp/' . rawurlencode($phone),
            preferToken: 'session_key',
            options: $options,
        );
    }
}
