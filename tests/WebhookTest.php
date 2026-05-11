<?php

declare(strict_types=1);

namespace Wapio\Tests;

use PHPUnit\Framework\TestCase;
use Wapio\Exceptions\WapioWebhookException;
use Wapio\Webhook\WebhookVerifier;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_with_enough_entropy';
    private const FROZEN_SEC = 1715000000;

    private static function sign(int $timestampSec, string $body, ?string $secret = null): string
    {
        $mac = hash_hmac('sha256', $timestampSec . '.' . $body, $secret ?? self::SECRET);
        return "t={$timestampSec},v1={$mac}";
    }

    private static function now(): callable
    {
        return static fn (): int => self::FROZEN_SEC;
    }

    public function test_verify_accepts_fresh_signature(): void
    {
        $body = json_encode(['event_type' => 'message_received', 'ok' => true]);
        $header = self::sign(self::FROZEN_SEC, $body);
        $this->assertTrue(WebhookVerifier::verify(self::SECRET, $header, $body, now: self::now()));
    }

    public function test_verify_rejects_tampered_body(): void
    {
        $body = '{"event_type":"message_received"}';
        $header = self::sign(self::FROZEN_SEC, $body);
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, $body . 'X', now: self::now()));
    }

    public function test_verify_rejects_wrong_secret(): void
    {
        $body = '{}';
        $header = self::sign(self::FROZEN_SEC, $body, 'whsec_wrong_xxxxxxxxxxxxxx');
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, $body, now: self::now()));
    }

    public function test_verify_rejects_expired_timestamp_default_tolerance(): void
    {
        $body = '{}';
        $header = self::sign(self::FROZEN_SEC - 301, $body);
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, $body, now: self::now()));
    }

    public function test_verify_rejects_future_timestamp_default_tolerance(): void
    {
        $body = '{}';
        $header = self::sign(self::FROZEN_SEC + 301, $body);
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, $body, now: self::now()));
    }

    public function test_verify_honors_custom_tolerance(): void
    {
        $body = '{}';
        $header = self::sign(self::FROZEN_SEC - 30, $body);
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, $body, toleranceSeconds: 10, now: self::now()));
        $this->assertTrue(WebhookVerifier::verify(self::SECRET, $header, $body, toleranceSeconds: 60, now: self::now()));
    }

    /** @dataProvider malformedHeaders */
    public function test_verify_rejects_malformed_header(?string $header): void
    {
        $this->assertFalse(WebhookVerifier::verify(self::SECRET, $header, '{}', now: self::now()));
    }

    /** @return array<string, array{0: ?string}> */
    public static function malformedHeaders(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'garbage' => ['garbage'],
            'missing v1' => ["t=" . self::FROZEN_SEC],
            'missing t' => ['v1=abc'],
            'non-numeric t' => ['t=abc,v1=deadbeef'],
        ];
    }

    public function test_handle_returns_parsed_event_on_valid_sig(): void
    {
        $event = [
            'event_type' => 'message_received',
            'account_id' => 'bps_acc_x',
            'session_id' => 'bps_sess_ws_x',
            'emitted_at' => self::FROZEN_SEC * 1000,
            'payload' => ['from' => '+15551234567'],
        ];
        $body = json_encode($event);
        $header = self::sign(self::FROZEN_SEC, $body);

        $result = WebhookVerifier::handle(self::SECRET, $header, $body, now: self::now());

        $this->assertSame('message_received', $result->eventType);
        $this->assertSame('bps_sess_ws_x', $result->sessionId);
        $this->assertSame(['from' => '+15551234567'], $result->payload);
        $this->assertSame($event, $result->raw);
    }

    public function test_handle_throws_401_on_bad_signature(): void
    {
        try {
            WebhookVerifier::handle(self::SECRET, 't=1,v1=deadbeef', '{}', now: self::now());
            $this->fail('expected WapioWebhookException');
        } catch (WapioWebhookException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }

    public function test_handle_throws_400_on_invalid_json(): void
    {
        $bad = 'not json {';
        $header = self::sign(self::FROZEN_SEC, $bad);
        try {
            WebhookVerifier::handle(self::SECRET, $header, $bad, now: self::now());
            $this->fail('expected WapioWebhookException');
        } catch (WapioWebhookException $e) {
            $this->assertSame(400, $e->getStatusCode());
        }
    }

    public function test_handle_throws_400_on_non_object_json(): void
    {
        $body = json_encode(['not', 'an', 'object']);
        $header = self::sign(self::FROZEN_SEC, $body);
        try {
            WebhookVerifier::handle(self::SECRET, $header, $body, now: self::now());
            $this->fail('expected WapioWebhookException');
        } catch (WapioWebhookException $e) {
            $this->assertSame(400, $e->getStatusCode());
        }
    }
}
