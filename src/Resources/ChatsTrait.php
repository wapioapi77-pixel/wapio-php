<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait ChatsTrait
{
    /** @param array<string,mixed> $options */
    public function getChats(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/chats', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getChat(string $chatJid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/chats/' . rawurlencode($chatJid),
            options: $options,
        );
    }

    /**
     * @param array<int,string>|null $messageIds
     * @param array<string,mixed> $options
     */
    public function markChatRead(string $chatJid, ?array $messageIds = null, array $options = []): Response
    {
        $body = $messageIds !== null ? ['message_ids' => $messageIds] : [];
        return $this->http->request(
            method: 'POST',
            path: '/api/chats/' . rawurlencode($chatJid) . '/read',
            body: $body,
            options: $options,
        );
    }
}
