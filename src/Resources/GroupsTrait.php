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
}
