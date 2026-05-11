<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

/**
 * Message send + lifecycle methods.
 *
 * Pulled into Wapio\Wapio as a trait. Flat namespacing for parity with the
 * Node / Python SDKs; camelCase per PSR-12.
 *
 * @method \Wapio\Http\HttpClient http()
 */
trait MessagesTrait
{
    /**
     * Generic send dispatcher. Prefer the per-type helpers for friendlier kwargs.
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function send(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/send-message',
            idempotent: true,
            preferToken: 'session_key',
            body: $payload,
            options: $options,
        );
    }

    /**
     * @param array{to?:string,channel_id?:string,text:string,quoted_message_id?:string,mentions?:array<int,string>} $payload
     * @param array<string,mixed> $options
     */
    public function sendText(array $payload, array $options = []): Response
    {
        return $this->send($payload, $options);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function sendImage(array $payload, array $options = []): Response
    {
        return $this->send(array_merge($payload, ['kind' => 'image']), $options);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function sendVideo(array $payload, array $options = []): Response
    {
        return $this->send(array_merge($payload, ['kind' => 'video']), $options);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function sendAudio(array $payload, array $options = []): Response
    {
        return $this->send(array_merge($payload, ['kind' => 'audio']), $options);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function sendDocument(array $payload, array $options = []): Response
    {
        return $this->send(array_merge($payload, ['kind' => 'document']), $options);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function sendSticker(array $payload, array $options = []): Response
    {
        return $this->send(array_merge($payload, ['kind' => 'sticker']), $options);
    }

    /**
     * @param array{to:string,location:array{latitude:float|int,longitude:float|int,name?:string,address?:string}} $payload
     * @param array<string,mixed> $options
     */
    public function sendLocation(array $payload, array $options = []): Response
    {
        return $this->send($payload, $options);
    }

    /**
     * @param array{to:string,contact:array<string,mixed>} $payload
     * @param array<string,mixed> $options
     */
    public function sendContact(array $payload, array $options = []): Response
    {
        return $this->send($payload, $options);
    }

    /**
     * @param array{to:string,poll:array{question:string,options:array<int,string>,multiSelect?:bool}} $payload
     * @param array<string,mixed> $options
     */
    public function sendPoll(array $payload, array $options = []): Response
    {
        return $this->send($payload, $options);
    }

    /**
     * Edit a previously-sent text message by its bps_msg_* id.
     *
     * @param array<string,mixed> $options
     */
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
    public function resendMessage(string $messageId, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/messages/' . rawurlencode($messageId) . '/resend',
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

    /**
     * @param array<int,string> $messageIds
     * @param array<string,mixed> $options
     */
    public function markMessagesRead(array $messageIds, ?string $chatJid = null, array $options = []): Response
    {
        $body = ['message_ids' => $messageIds];
        if ($chatJid !== null) {
            $body['chat_jid'] = $chatJid;
        }
        return $this->http->request(
            method: 'POST',
            path: '/api/messages/read',
            preferToken: 'session_key',
            body: $body,
            options: $options,
        );
    }

    /**
     * Update typing / online presence for a chat.
     * `presence` is one of: available, unavailable, composing, recording, paused.
     *
     * @param array<string,mixed> $options
     */
    public function sendPresenceUpdate(string $to, string $presence, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/send-presence-update',
            preferToken: 'session_key',
            body: ['to' => $to, 'presence' => $presence],
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
