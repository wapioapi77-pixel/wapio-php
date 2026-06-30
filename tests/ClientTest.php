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
use Wapio\Exceptions\WebhookVerificationException;
use Wapio\Http\RetryConfig;
use Wapio\Webhook;
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

    public function test_send_channel_message_sends_channel_id(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['msgId' => 'x', 'jid' => '120363@newsletter', 'status' => 'queued']])),
        ], $history);

        $wapio->sendChannelMessage('120363@newsletter', 'Channel update');

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame(['channel_id' => '120363@newsletter', 'text' => 'Channel update'], $body);
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

    public function test_group_participant_add_remove_paths(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
        ], $history);

        $wapio->addGroupParticipants('120363@g.us', ['15551234567@s.whatsapp.net']);
        $wapio->removeGroupParticipants('120363@g.us', ['15551234567@s.whatsapp.net']);

        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/participants/add', (string) $history[0]['request']->getUri());
        $this->assertSame('POST', $history[1]['request']->getMethod());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/participants/remove', (string) $history[1]['request']->getUri());
    }

    public function test_advanced_message_helpers_serialize_supported_payloads(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
        ], $history);

        $wapio->sendAudio(['to' => '+1', 'audioUrl' => 'https://example.com/a.ogg', 'voice' => true]);
        $wapio->sendPoll(['to' => '+1', 'name' => 'Pick one', 'options' => ['A', 'B']]);
        $wapio->sendQuotedMessage(['to' => '+1', 'text' => 'reply', 'quotedMessageId' => 'bps_msg_parent']);

        $audio = json_decode((string) $history[0]['request']->getBody(), true);
        $poll = json_decode((string) $history[1]['request']->getBody(), true);
        $quoted = json_decode((string) $history[2]['request']->getBody(), true);

        $this->assertSame('audio', $audio['kind']);
        $this->assertSame('https://example.com/a.ogg', $audio['audioUrl']);
        $this->assertSame(['question' => 'Pick one', 'options' => ['A', 'B'], 'multiSelect' => null], $poll['poll']);
        $this->assertSame('bps_msg_parent', $quoted['quoted_message_id']);
        $this->assertSame('bps_msg_parent', $quoted['replyTo']);
    }

    public function test_message_actions_logs_and_presence_paths(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['messages' => []]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['events' => []]])),
        ], $history);

        $wapio->resendFailedMessage('bps_msg_x');
        $wapio->markMessageAsRead(['bps_msg_x'], '1555@s.whatsapp.net', 'bps_sess_ws_x');
        $wapio->sendPresenceUpdate(['to' => '1555@s.whatsapp.net', 'presence' => 'composing']);
        $wapio->getMessageLogs('bps_sess_ws_x', ['limit' => 10]);
        $wapio->getSessionLogs('bps_sess_ws_x', ['cursor' => 20]);

        $this->assertSame('https://api.wapio.test/api/messages/bps_msg_x/resend', (string) $history[0]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/messages/read', (string) $history[1]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/send-presence-update', (string) $history[2]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/whatsapp-sessions/bps_sess_ws_x/message-logs?limit=10', (string) $history[3]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/whatsapp-sessions/bps_sess_ws_x/session-logs?cursor=20', (string) $history[4]['request']->getUri());
    }

    public function test_contact_and_group_advanced_paths(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['ok' => true]])),
        ], $history);

        $wapio->createOrUpdateContact(['phone' => '+15551234567', 'name' => 'M']);
        $wapio->promoteGroupParticipants('120363@g.us', ['1555@s.whatsapp.net']);
        $wapio->demoteGroupParticipants('120363@g.us', ['1555@s.whatsapp.net']);
        $wapio->getGroupInviteLink('120363@g.us');
        $wapio->getGroupInviteMetadata('ABCD1234');
        $wapio->acceptGroupInvite('ABCD1234', 'bps_sess_ws_x');
        $wapio->leaveGroup('120363@g.us');

        $this->assertSame('PUT', $history[0]['request']->getMethod());
        $this->assertSame('https://api.wapio.test/api/contacts', (string) $history[0]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/participants/promote', (string) $history[1]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/participants/demote', (string) $history[2]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/invite-link', (string) $history[3]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/invite/ABCD1234', (string) $history[4]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/invite/accept', (string) $history[5]['request']->getUri());
        $this->assertSame('https://api.wapio.test/api/groups/120363%40g.us/leave', (string) $history[6]['request']->getUri());
    }

    public function test_webhook_signature_verification_and_parsing(): void
    {
        $body = '{"type":"message.received","data":{"message_id":"bps_msg_x"}}';
        $timestamp = time();
        $signature = Webhook::computeSignature('whsec_test', $timestamp, $body);
        $event = Webhook::constructEvent('whsec_test', "t={$timestamp},v1={$signature}", $body);

        $this->assertSame('message.received', $event['type']);
    }

    public function test_webhook_signature_rejects_invalid_signature(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhook::verifySignature('whsec_test', 't=' . time() . ',v1=bad', '{}');
    }

    public function test_media_decrypt_uses_api_decrypt_media(): void
    {
        $history = [];
        $wapio = $this->makeClient([
            new GuzzleResponse(200, [], json_encode(['success' => true, 'data' => ['media_id' => 'bps_im_x']])),
        ], $history);

        $wapio->decryptMedia(['mediaKey' => 'key', 'directPath' => '/v/t', 'type' => 'image']);

        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame('https://api.wapio.test/api/decrypt-media', (string) $history[0]['request']->getUri());
    }

    public function test_create_session_with_only_apikey_throws_config_exception(): void
    {
        // No mock response — the SDK must fail BEFORE the network call.
        $wapio = $this->makeClient([]);
        $this->expectException(WapioConfigException::class);
        $wapio->createSession(['label' => 'x']);
    }

    public function test_regenerate_api_key_with_only_apikey_throws_config_exception(): void
    {
        $wapio = $this->makeClient([]);
        $this->expectException(WapioConfigException::class);
        $wapio->regenerateApiKey('bps_sess_ws_x');
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
