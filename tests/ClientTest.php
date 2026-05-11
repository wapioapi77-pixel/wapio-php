<?php

declare(strict_types=1);

namespace Wapio\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Wapio\Exceptions\WapioApiException;
use Wapio\Exceptions\WapioConfigException;
use Wapio\Http\RetryConfig;
use Wapio\Wapio;

final class ClientTest extends TestCase
{
    /**
     * @param list<GuzzleResponse> $responses
     * @param array<int, RequestInterface> $history Captured requests appended here.
     */
    private function makeClient(array $responses, array &$history = [], bool $retry = false): Wapio
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        return new Wapio(
            apiKey: 'bps_sk_test',
            baseUrl: 'https://api.wapio.test',
            retry: new RetryConfig(enabled: $retry),
            guzzle: $guzzle,
        );
    }

    public function test_construct_without_any_token_throws(): void
    {
        $this->expectException(WapioConfigException::class);
        new Wapio();
    }

    public function test_send_text_uses_bearer_idempotency_and_unwraps_envelope(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, ['content-type' => 'application/json'], json_encode([
                'success' => true,
                'data' => ['msgId' => 'bps_msg_x', 'jid' => '15551234567@s.whatsapp.net', 'status' => 'in_progress'],
            ])),
        ], $history);

        $result = $wapio->sendText(['to' => '+15551234567', 'text' => 'Hi']);

        $this->assertSame('bps_msg_x', $result->data['msgId']);
        $this->assertSame(200, $result->statusCode);

        $req = $history[0]['request'];
        $this->assertSame('https://api.wapio.test/api/send-message', (string) $req->getUri());
        $this->assertSame('Bearer bps_sk_test', $req->getHeaderLine('Authorization'));
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $req->getHeaderLine('Idempotency-Key'));
        $this->assertSame('{"to":"+15551234567","text":"Hi"}', (string) $req->getBody());
    }

    public function test_caller_supplied_idempotency_key_wins(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['msgId' => 'x', 'jid' => 'y', 'status' => 'queued']])),
        ], $history);

        $wapio->sendText(['to' => '+1', 'text' => 'hi'], ['idempotency_key' => 'fixed-key-123']);

        $this->assertSame('fixed-key-123', $history[0]['request']->getHeaderLine('Idempotency-Key'));
    }

    public function test_422_throws_wapio_api_exception_with_reason_code(): void
    {
        $wapio = $this->makeClient([
            new GuzzleResponse(422, [], json_encode([
                'success' => false,
                'message' => 'session_id in request does not match the session this API key is bound to.',
                'reason_code' => 'validation_failed',
            ])),
        ]);

        try {
            $wapio->sendText(['to' => '+1', 'text' => 'x']);
            $this->fail('expected WapioApiException');
        } catch (WapioApiException $e) {
            $this->assertSame(422, $e->statusCode);
            $this->assertSame('validation_failed', $e->reasonCode);
        }
    }

    public function test_429_surfaces_rate_limit_and_retries(): void
    {
        $history = [];
        $rl = ['retry-after' => '0', 'x-ratelimit-limit' => '256', 'x-ratelimit-remaining' => '0'];
        $mock = new MockHandler([
            new GuzzleResponse(429, $rl, json_encode(['success' => false, 'reason_code' => 'rate_limited'])),
            new GuzzleResponse(429, $rl, json_encode(['success' => false, 'reason_code' => 'rate_limited'])),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        $wapio = new Wapio(
            apiKey: 'bps_sk_test',
            baseUrl: 'https://api.wapio.test',
            retry: new RetryConfig(enabled: true, maxRetries: 1, initialBackoff: 0.001, maxBackoff: 0.001),
            guzzle: $guzzle,
        );

        try {
            $wapio->sendText(['to' => '+1', 'text' => 'x']);
            $this->fail('expected WapioApiException');
        } catch (WapioApiException $e) {
            $this->assertSame(429, $e->statusCode);
            $this->assertNotNull($e->rateLimit);
            $this->assertSame(256, $e->rateLimit->limit);
            $this->assertSame(0, $e->rateLimit->remaining);
        }
        $this->assertCount(2, $history, '1 initial + 1 retry');
    }

    public function test_list_sessions_serializes_status_array(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['data' => [], 'next_cursor' => null]])),
        ], $history);

        $wapio->listSessions(['status' => ['connected', 'pending'], 'limit' => 10, 'before_id' => 'bps_sess_ws_x']);

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringContainsString('status=connected%2Cpending', $uri);
        $this->assertStringContainsString('limit=10', $uri);
        $this->assertStringContainsString('before_id=bps_sess_ws_x', $uri);
    }

    public function test_create_session_with_only_apikey_throws_config_exception(): void
    {
        // No mock response — the SDK must fail BEFORE the network call.
        $wapio = $this->makeClient([]);
        $this->expectException(WapioConfigException::class);
        $wapio->createSession(['label' => 'x']);
    }

    public function test_dashboard_overview_with_only_apikey_throws_config_exception(): void
    {
        $wapio = $this->makeClient([]);
        $this->expectException(WapioConfigException::class);
        $wapio->getDashboardOverview();
    }

    public function test_regenerate_api_key_with_only_apikey_throws_config_exception(): void
    {
        $wapio = $this->makeClient([]);
        $this->expectException(WapioConfigException::class);
        $wapio->regenerateApiKey('bps_sess_ws_x');
    }

    public function test_alias_get_all_whatsapp_sessions_routes_to_list_sessions(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['data' => [], 'next_cursor' => null]])),
        ], $history);

        $wapio->getAllWhatsAppSessions();

        $req = $history[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertStringStartsWith('https://api.wapio.test/v1/whatsapp-sessions', (string) $req->getUri());
    }

    public function test_alias_send_message_with_mentions_routes_to_send_text(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['msgId' => 'x', 'jid' => 'y', 'status' => 'queued']])),
        ], $history);

        $wapio->sendMessageWithMentions([
            'to' => '<g>@g.us',
            'text' => '@123 hi',
            'mentions' => ['123@s.whatsapp.net'],
        ]);

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('@123 hi', $body['text']);
        $this->assertSame(['123@s.whatsapp.net'], $body['mentions']);
    }

    public function test_dual_token_routes_pat_only_endpoints_to_pat(): void
    {
        $history = [];
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['session' => ['session_id' => 'x']]])),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        $wapio = new Wapio(
            apiKey: 'bps_sk_sk',
            personalAccessToken: 'bps_pat_pat',
            baseUrl: 'https://api.wapio.test',
            retry: new RetryConfig(enabled: false),
            guzzle: $guzzle,
        );

        $wapio->createSession(['label' => 'x']);

        $this->assertSame('Bearer bps_pat_pat', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function test_dual_token_routes_send_message_to_session_key(): void
    {
        $history = [];
        $mock = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['msgId' => 'x', 'jid' => 'y', 'status' => 'queued']])),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        $wapio = new Wapio(
            apiKey: 'bps_sk_sk',
            personalAccessToken: 'bps_pat_pat',
            baseUrl: 'https://api.wapio.test',
            retry: new RetryConfig(enabled: false),
            guzzle: $guzzle,
        );

        $wapio->sendText(['to' => '+1', 'text' => 'hi']);

        $this->assertSame('Bearer bps_sk_sk', $history[0]['request']->getHeaderLine('Authorization'));
    }
}
