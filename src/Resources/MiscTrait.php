<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait MiscTrait
{
    /** @param array<string,mixed> $options */
    public function onWhatsapp(string $phone, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/on-whatsapp/' . rawurlencode($phone),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function pnFromLid(string $lid, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/pn-from-lid/' . rawurlencode($lid),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function lidFromPn(string $pn, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/api/lid-from-pn/' . rawurlencode($pn),
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getUser(array $options = []): Response
    {
        return $this->http->request(method: 'GET', path: '/api/user', options: $options);
    }

    /** @param array<string,mixed> $options */
    public function getOperationStatus(string $operationId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/operations/' . rawurlencode($operationId),
            options: $options,
        );
    }

    /**
     * Account-wide dashboard aggregates. PAT-only.
     *
     * @param array<string,mixed> $options
     */
    public function getDashboardOverview(array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/me/dashboard-overview',
            patOnly: true,
            options: $options,
        );
    }
}
