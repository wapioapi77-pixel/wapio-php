<?php

declare(strict_types=1);

namespace Wapio;

use GuzzleHttp\Client as GuzzleClient;
use Wapio\Http\HttpClient;
use Wapio\Http\RetryConfig;
use Wapio\Resources\ContactsTrait;
use Wapio\Resources\GroupsTrait;
use Wapio\Resources\MediaTrait;
use Wapio\Resources\MessagesTrait;
use Wapio\Resources\MiscTrait;
use Wapio\Resources\SessionsTrait;

/**
 * Synchronous Wapio API client.
 *
 * Provide a session-scoped Bearer key (`apiKey: bps_sk_...`) for send/read
 * paths, or a Personal Access Token (`personalAccessToken: bps_pat_...`)
 * for lifecycle/account routes. Pass both if you need both — the SDK picks
 * the correct token per endpoint.
 *
 * Example:
 *
 *     use Wapio\Wapio;
 *
 *     $wapio = new Wapio(apiKey: 'bps_sk_...');
 *     $result = $wapio->sendText(['to' => '+15551234567', 'text' => 'Hello!']);
 *     echo $result->data['msgId'];
 *     echo $result->rateLimit->remaining;
 */
class Wapio
{
    use MessagesTrait;
    use SessionsTrait;
    use ContactsTrait;
    use GroupsTrait;
    use MediaTrait;
    use MiscTrait;

    protected HttpClient $http;

    /**
     * @param array<string,string> $defaultHeaders
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $personalAccessToken = null,
        ?string $baseUrl = null,
        float $timeout = HttpClient::DEFAULT_TIMEOUT,
        ?RetryConfig $retry = null,
        array $defaultHeaders = [],
        ?GuzzleClient $guzzle = null,
    ) {
        $this->http = new HttpClient(
            personalAccessToken: $personalAccessToken,
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            timeout: $timeout,
            retry: $retry,
            defaultHeaders: $defaultHeaders,
            guzzle: $guzzle,
        );
    }

    /**
     * Factory accepting a config array. Useful for Laravel service-container
     * resolution and for IDEs that prefer associative-array construction.
     *
     * @param array{
     *     api_key?: string,
     *     personal_access_token?: string,
     *     base_url?: string,
     *     timeout?: float,
     *     retry?: RetryConfig,
     *     default_headers?: array<string,string>,
     *     guzzle?: GuzzleClient
     * } $config
     */
    public static function create(array $config): self
    {
        return new self(
            apiKey: $config['api_key'] ?? null,
            personalAccessToken: $config['personal_access_token'] ?? null,
            baseUrl: $config['base_url'] ?? null,
            timeout: $config['timeout'] ?? HttpClient::DEFAULT_TIMEOUT,
            retry: $config['retry'] ?? null,
            defaultHeaders: $config['default_headers'] ?? [],
            guzzle: $config['guzzle'] ?? null,
        );
    }

}
