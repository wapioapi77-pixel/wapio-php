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
    public function getGroup(string $jid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/' . rawurlencode($jid),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getGroupMetadata(string $jid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/' . rawurlencode($jid) . '/metadata',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getGroupParticipants(string $jid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/' . rawurlencode($jid) . '/participants',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getGroupProfilePicture(string $groupJid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/' . rawurlencode($groupJid) . '/picture',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getGroupInviteLink(string $groupJid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/' . rawurlencode($groupJid) . '/invite-link',
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getGroupInviteInfo(string $inviteCode, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/groups/invite/' . rawurlencode($inviteCode),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function acceptGroupInvite(string $inviteCode, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/invite/accept',
            body: ['invite_code' => $inviteCode],
            options: $options,
        );
    }

    /**
     * @param array{subject:string,participants?:array<int,string>} $payload
     * @param array<string,mixed> $options
     */
    public function createGroup(array $payload, array $options = []): Response
    {
        $body = [
            'subject' => $payload['subject'],
            'participants' => $payload['participants'] ?? [],
        ];
        return $this->http->request(
            method: 'POST',
            path: '/api/groups',
            body: $body,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function leaveGroup(string $groupJid, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/' . rawurlencode($groupJid) . '/leave',
            options: $options,
        );
    }

    /**
     * @param array<string,mixed> $settings
     * @param array<string,mixed> $options
     */
    public function updateGroupSettings(string $groupJid, array $settings, array $options = []): Response
    {
        return $this->http->request(
            method: 'PUT',
            path: '/api/groups/' . rawurlencode($groupJid) . '/settings',
            body: $settings,
            options: $options,
        );
    }

    /**
     * @param array<int,string> $participants
     * @param array<string,mixed> $options
     */
    public function addGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/' . rawurlencode($groupJid) . '/participants',
            body: ['participants' => $participants],
            options: $options,
        );
    }

    /**
     * @param array<int,string> $participants
     * @param array<string,mixed> $options
     */
    public function removeGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(
            method: 'DELETE',
            path: '/api/groups/' . rawurlencode($groupJid) . '/participants',
            body: ['participants' => $participants],
            options: $options,
        );
    }

    /**
     * @param array<int,string> $participants
     * @param array<string,mixed> $options
     */
    public function promoteGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/' . rawurlencode($groupJid) . '/participants/promote',
            body: ['participants' => $participants],
            options: $options,
        );
    }

    /**
     * @param array<int,string> $participants
     * @param array<string,mixed> $options
     */
    public function demoteGroupParticipants(string $groupJid, array $participants, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/groups/' . rawurlencode($groupJid) . '/participants/demote',
            body: ['participants' => $participants],
            options: $options,
        );
    }

    /**
     * `$action` is one of: add, remove, promote, demote.
     *
     * @param array<int,string> $participants
     * @param array<string,mixed> $options
     */
    public function updateGroupParticipants(
        string $groupJid,
        string $action,
        array $participants,
        array $options = [],
    ): Response {
        return $this->http->request(
            method: 'PUT',
            path: '/api/groups/' . rawurlencode($groupJid) . '/participants/update',
            body: ['action' => $action, 'participants' => $participants],
            options: $options,
        );
    }
}
