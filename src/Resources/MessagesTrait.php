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

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function send(array $payload, array $options = []): Response
    {
        return $this->sendPublic($payload, $options);
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

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendAudio(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'kind' => 'audio',
            'audioUrl' => $payload['audioUrl'] ?? null,
            'url' => $payload['url'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
            'voice' => $payload['voice'] ?? null,
            'ptt' => $payload['ptt'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendSticker(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'kind' => 'sticker',
            'stickerUrl' => $payload['stickerUrl'] ?? null,
            'url' => $payload['url'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendContact(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'contact' => $payload['contact'] ?? null,
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendLocation(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'location' => $payload['location'] ?? [
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'name' => $payload['name'] ?? null,
                'address' => $payload['address'] ?? null,
            ],
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendPoll(array $payload, array $options = []): Response
    {
        return $this->sendPublic([
            'to' => $payload['to'] ?? null,
            'channel_id' => $payload['channel_id'] ?? null,
            'poll' => $payload['poll'] ?? [
                'question' => $payload['question'] ?? $payload['name'] ?? null,
                'options' => $payload['options'] ?? null,
                'multiSelect' => $payload['multiSelect'] ?? $payload['multi_select'] ?? null,
            ],
        ], $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendQuotedMessage(array $payload, array $options = []): Response
    {
        $quotedMessageId = $payload['quoted_message_id'] ?? $payload['quotedMessageId'] ?? $payload['replyTo'] ?? null;

        return $this->sendPublic(array_merge($payload, [
            'quoted_message_id' => $quotedMessageId,
            'replyTo' => $quotedMessageId,
        ]), $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendViewOnceMessage(array $payload, array $options = []): Response
    {
        return $this->sendPublic(array_merge($payload, ['viewOnce' => true]), $options);
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
    public function resendFailedMessage(string $messageId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/messages/' . rawurlencode($messageId) . '/resend',
            preferToken: 'session_key',
            options: $options,
        );
    }

    /**
     * @param array<int,string> $messageIds
     * @param array<string,mixed> $options
     */
    public function markMessageAsRead(array $messageIds, ?string $chatJid = null, ?string $sessionId = null, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/messages/read',
            preferToken: 'session_key',
            body: array_filter([
                'message_ids' => $messageIds,
                'chat_jid' => $chatJid,
                'session_id' => $sessionId,
            ], static fn ($value) => $value !== null),
            options: $options,
        );
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function sendPresenceUpdate(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/send-presence-update',
            preferToken: 'session_key',
            body: $payload,
            options: $options,
        );
    }

    /** @param array{cursor?:string|int,limit?:int} $params @param array<string,mixed> $options */
    public function getMessageLogs(string $sessionId, array $params = [], array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/whatsapp-sessions/' . rawurlencode($sessionId) . '/message-logs',
            preferToken: 'session_key',
            query: $params,
            options: $options,
        );
    }

    /** @param array{cursor?:string|int,limit?:int} $params @param array<string,mixed> $options */
    public function getSessionLogs(string $sessionId, array $params = [], array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/whatsapp-sessions/' . rawurlencode($sessionId) . '/session-logs',
            preferToken: 'session_key',
            query: $params,
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
