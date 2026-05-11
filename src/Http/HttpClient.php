<?php

declare(strict_types=1);

namespace Wapio\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Wapio\Exceptions\WapioApiException;
use Wapio\Exceptions\WapioConfigException;

/**
 * @internal Customers should use Wapio\Wapio.
 */
final class HttpClient
{
    public const DEFAULT_BASE_URL = 'https://api.wapio.io';
    public const DEFAULT_TIMEOUT = 60.0;
    private const SDK_VERSION = '0.1.0';
    private const USER_AGENT = 'wapio-php/' . self::SDK_VERSION;

    private ?string $personalAccessToken;
    private ?string $apiKey;
    private string $baseUrl;
    private float $timeout;
    private RetryConfig $retry;
    /** @var array<string,string> */
    private array $defaultHeaders;
    private Client $guzzle;

    /**
     * @param array<string,string> $defaultHeaders
     */
    public function __construct(
        ?string $personalAccessToken = null,
        ?string $apiKey = null,
        ?string $baseUrl = null,
        float $timeout = self::DEFAULT_TIMEOUT,
        ?RetryConfig $retry = null,
        array $defaultHeaders = [],
        ?Client $guzzle = null,
    ) {
        if ($personalAccessToken === null && $apiKey === null) {
            throw new WapioConfigException(
                'Provide at least one of `personalAccessToken` (bps_pat_*) or `apiKey` (bps_sk_*).'
            );
        }
        $this->personalAccessToken = $personalAccessToken;
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = $timeout;
        $this->retry = $retry ?? new RetryConfig();
        $this->defaultHeaders = array_merge(['User-Agent' => self::USER_AGENT], $defaultHeaders);
        $this->guzzle = $guzzle ?? new Client();
    }

    /**
     * Execute an internal request and return a wrapped Response.
     *
     * @param array<string,mixed>|null $body
     * @param array<string,mixed>|null $query
     * @param array<string,mixed>      $options Request-level options. Recognised keys:
     *                                          `idempotency_key`, `token_kind`, `timeout`,
     *                                          `headers` (array<string,string>).
     */
    public function request(
        string $method,
        string $path,
        bool $idempotent = false,
        ?string $preferToken = null,
        bool $patOnly = false,
        ?array $body = null,
        ?array $query = null,
        array $options = [],
    ): Response {
        $token = $this->selectToken(preferToken: $preferToken, patOnly: $patOnly, override: $options['token_kind'] ?? null);
        if ($token === null) {
            if ($patOnly) {
                throw new WapioConfigException(
                    "Endpoint {$path} requires a Personal Access Token (bps_pat_*). "
                    . 'Pass `personalAccessToken` when constructing the client.'
                );
            }
            throw new WapioConfigException('No auth token available.');
        }

        $url = $this->buildUrl($path);
        $headers = array_merge(
            $this->defaultHeaders,
            (array) ($options['headers'] ?? []),
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        );
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }
        if ($idempotent) {
            $headers['Idempotency-Key'] = $options['idempotency_key'] ?? Uuid::uuid4()->toString();
        }

        $guzzleOptions = [
            'headers' => $headers,
            'timeout' => $options['timeout'] ?? $this->timeout,
            'http_errors' => false,
        ];
        if ($body !== null) {
            $guzzleOptions['body'] = json_encode($body, JSON_UNESCAPED_SLASHES);
        }
        if ($query) {
            $guzzleOptions['query'] = array_filter(
                $query,
                static fn ($v) => $v !== null
            );
        }

        $maxAttempts = $this->retry->enabled ? $this->retry->maxRetries + 1 : 1;
        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $response = $this->guzzle->request($method, $url, $guzzleOptions);
            } catch (ConnectException $e) {
                $lastException = $e;
                if ($attempt + 1 >= $maxAttempts) {
                    break;
                }
                usleep((int) ($this->computeBackoff(null, $attempt) * 1_000_000));
                continue;
            } catch (RequestException $e) {
                $r = $e->getResponse();
                if ($r === null) {
                    $lastException = $e;
                    if ($attempt + 1 >= $maxAttempts) {
                        break;
                    }
                    usleep((int) ($this->computeBackoff(null, $attempt) * 1_000_000));
                    continue;
                }
                $response = $r;
            } catch (GuzzleException $e) {
                $lastException = $e;
                if ($attempt + 1 >= $maxAttempts) {
                    break;
                }
                usleep((int) ($this->computeBackoff(null, $attempt) * 1_000_000));
                continue;
            }

            $rateLimit = $this->readRateLimit($response);
            $requestId = $response->getHeaderLine('x-request-id') ?: null;
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return $this->unwrapSuccess($response, $rateLimit, $requestId);
            }

            if ($statusCode === 429 && $attempt + 1 < $maxAttempts) {
                usleep((int) ($this->computeBackoff($rateLimit->retryAfterSeconds, $attempt) * 1_000_000));
                continue;
            }
            if ($statusCode >= 500 && $attempt + 1 < $maxAttempts) {
                usleep((int) ($this->computeBackoff(null, $attempt) * 1_000_000));
                continue;
            }

            throw $this->buildApiError($response, $rateLimit, $requestId);
        }

        /** @var \Throwable $lastException */
        throw $lastException;
    }

    private function selectToken(?string $preferToken, bool $patOnly, ?string $override): ?string
    {
        if ($patOnly) {
            return $this->personalAccessToken;
        }
        if ($override === 'pat') {
            return $this->personalAccessToken ?? $this->apiKey;
        }
        if ($override === 'session_key') {
            return $this->apiKey ?? $this->personalAccessToken;
        }
        if ($preferToken === 'pat') {
            return $this->personalAccessToken ?? $this->apiKey;
        }
        if ($preferToken === 'session_key') {
            return $this->apiKey ?? $this->personalAccessToken;
        }
        return $this->personalAccessToken ?? $this->apiKey;
    }

    private function buildUrl(string $path): string
    {
        $sep = str_starts_with($path, '/') ? '' : '/';
        return $this->baseUrl . $sep . $path;
    }

    private function readRateLimit(ResponseInterface $response): RateLimitInfo
    {
        $intHeader = static function (string $name) use ($response): ?int {
            $raw = $response->getHeaderLine($name);
            if ($raw === '') {
                return null;
            }
            return is_numeric($raw) ? (int) $raw : null;
        };

        $retryAfterRaw = $response->getHeaderLine('retry-after');
        $retryAfter = null;
        if ($retryAfterRaw !== '') {
            if (is_numeric($retryAfterRaw)) {
                $retryAfter = (int) $retryAfterRaw;
            } else {
                $ts = strtotime($retryAfterRaw);
                if ($ts !== false) {
                    $retryAfter = max(0, $ts - time());
                }
            }
        }

        return new RateLimitInfo(
            limit: $intHeader('x-ratelimit-limit'),
            remaining: $intHeader('x-ratelimit-remaining'),
            resetTimestamp: $intHeader('x-ratelimit-reset'),
            retryAfterSeconds: $retryAfter,
        );
    }

    private function unwrapSuccess(ResponseInterface $response, RateLimitInfo $rateLimit, ?string $requestId): Response
    {
        if ($response->getStatusCode() === 204) {
            return new Response(null, $rateLimit, $requestId, $response->getStatusCode());
        }
        $body = (string) $response->getBody();
        if ($body === '') {
            return new Response(null, $rateLimit, $requestId, $response->getStatusCode());
        }
        $parsed = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response($body, $rateLimit, $requestId, $response->getStatusCode());
        }
        return new Response(
            $this->unwrapEnvelope($parsed),
            $rateLimit,
            $requestId,
            $response->getStatusCode(),
        );
    }

    /** @param mixed $parsed */
    private function unwrapEnvelope(mixed $parsed): mixed
    {
        if (is_array($parsed) && ($parsed['success'] ?? null) === true && array_key_exists('data', $parsed)) {
            return $parsed['data'];
        }
        return $parsed;
    }

    private function buildApiError(ResponseInterface $response, RateLimitInfo $rateLimit, ?string $requestId): WapioApiException
    {
        $apiMessage = $response->getReasonPhrase() ?: 'Wapio API error';
        $reasonCode = null;
        $errorDetails = null;
        $body = (string) $response->getBody();
        if ($body !== '') {
            $parsed = json_decode($body, true);
            if (is_array($parsed)) {
                $apiMessage = $parsed['message'] ?? $apiMessage;
                $reasonCode = $parsed['reason_code'] ?? null;
                if (isset($parsed['errors']) && is_array($parsed['errors'])) {
                    $errorDetails = $parsed['errors'];
                }
            }
        }
        return new WapioApiException(
            statusCode: $response->getStatusCode(),
            apiMessage: $apiMessage,
            reasonCode: $reasonCode,
            errorDetails: $errorDetails,
            requestId: $requestId,
            rateLimit: $rateLimit,
        );
    }

    private function computeBackoff(?int $retryAfterSeconds, int $attempt): float
    {
        if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            return min((float) $retryAfterSeconds, $this->retry->maxBackoff);
        }
        $jitter = 0.85 + (mt_rand(0, 30) / 100);
        return min($this->retry->initialBackoff * (2 ** $attempt) * $jitter, $this->retry->maxBackoff);
    }
}
