<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait GroupsTrait
{
    /** @param array<string,mixed> $options */
    public function getGroups(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getGroupMetadata(string $jid, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups/' . rawurlencode($jid) . '/metadata', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getGroupParticipants(string $jid, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups/' . rawurlencode($jid) . '/participants', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getGroupProfilePicture(string $groupJid, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups/' . rawurlencode($groupJid) . '/picture', options: $options);
    }

    /** @param array{subject:string,participants?:array<int,string>} $payload @param array<string,mixed> $options */
    public function createGroup(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups',
            body: ['subject' => $payload['subject'], 'participants' => $payload['participants'] ?? []],
            options: $options,
        );
    }

    /** @param array<string,mixed> $settings @param array<string,mixed> $options */
    public function updateGroupSettings(string $groupJid, array $settings, array $options = []): Response
    {
        return $this->http->request(method: 'PUT', path: '/api/groups/' . rawurlencode($groupJid) . '/settings', body: $settings, options: $options);
    }

    /** @param array<int,string> $participants @param array<string,mixed> $options */
    public function addGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/api/groups/' . rawurlencode($groupJid) . '/participants/add', body: ['participants' => $participants], options: $options);
    }

    /** @param array<int,string> $participants @param array<string,mixed> $options */
    public function removeGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/api/groups/' . rawurlencode($groupJid) . '/participants/remove', body: ['participants' => $participants], options: $options);
    }

    /** @param array<int,string> $participants @param array<string,mixed> $options */
    public function promoteGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/api/groups/' . rawurlencode($groupJid) . '/participants/promote', body: ['participants' => $participants], options: $options);
    }

    /** @param array<int,string> $participants @param array<string,mixed> $options */
    public function demoteGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(method: 'POST', path: '/api/groups/' . rawurlencode($groupJid) . '/participants/demote', body: ['participants' => $participants], options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getGroupInviteLink(string $groupJid, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups/' . rawurlencode($groupJid) . '/invite-link', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getGroupInviteMetadata(string $inviteCode, array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/groups/invite/' . rawurlencode($inviteCode), options: $options);
    }

    /** @param array<string,mixed> $options */
    public function acceptGroupInvite(string $inviteCode, ?string $sessionId = null, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/invite/accept',
            body: array_filter(['invite_code' => $inviteCode, 'session_id' => $sessionId], static fn ($value) => $value !== null),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function leaveGroup(string $groupJid, ?string $sessionId = null, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/' . rawurlencode($groupJid) . '/leave',
            body: $sessionId === null ? null : ['session_id' => $sessionId],
            options: $options,
        );
    }

    /**
     * @param array<int,string> $mentions
     * @param array<string,mixed> $options
     */
    public function sendGroupMessage(string $groupJid, string $text, array $mentions = [], array $options = []): Response
    {
        return $this->send([
            'to' => $groupJid,
            'text' => $text,
            'mentions' => $mentions ?: null,
        ], $options);
    }
}
