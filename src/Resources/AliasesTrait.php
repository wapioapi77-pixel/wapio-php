<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

/**
 * Long-form method aliases.
 *
 * Wapio's preferred names are short (`listSessions`, `getSession`,
 * `getSessionQrCode`, …). The aliases below give every method a more
 * descriptive long form (`getAllWhatsAppSessions`,
 * `getWhatsAppSessionDetails`, `getWhatsAppSessionQrCode`, …) for
 * codebases that prefer the verbose style.
 *
 * Both forms call the same underlying transport — pick whichever
 * reads better in your code.
 */
trait AliasesTrait
{
    // ---- sessions ----------------------------------------------------------

    /** @param array<string,mixed> $params @param array<string,mixed> $options */
    public function getAllWhatsAppSessions(array $params = [], array $options = []): Response
    {
        return $this->listSessions($params, $options);
    }

    /** @param array<string,mixed> $options */
    public function getWhatsAppSessionDetails(string $sessionId, array $options = []): Response
    {
        return $this->getSession($sessionId, $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function createWhatsAppSession(array $payload, array $options = []): Response
    {
        return $this->createSession($payload, $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function updateWhatsAppSession(string $sessionId, array $payload, array $options = []): Response
    {
        return $this->updateSession($sessionId, $payload, $options);
    }

    /** @param array<string,mixed> $options */
    public function deleteWhatsAppSession(string $sessionId, array $options = []): Response
    {
        return $this->deleteSession($sessionId, $options);
    }

    /** @param array<string,mixed> $options */
    public function connectWhatsAppSession(string $sessionId, array $options = []): Response
    {
        return $this->connectSession($sessionId, $options);
    }

    /** @param array<string,mixed> $options */
    public function disconnectWhatsAppSession(string $sessionId, array $options = []): Response
    {
        return $this->disconnectSession($sessionId, $options);
    }

    /** @param array<string,mixed> $options */
    public function getWhatsAppSessionQrCode(string $sessionId, array $options = []): Response
    {
        return $this->getSessionQrCode($sessionId, $options);
    }

    /**
     * Long-form alias for {@see getUser()} — the WhatsApp profile of the
     * connected account for this session.
     *
     * @param array<string,mixed> $options
     */
    public function getSessionUserInfo(array $options = []): Response
    {
        return $this->getUser($options);
    }

    // ---- contacts ----------------------------------------------------------

    /** @param array<string,mixed> $options */
    public function getContactInfo(string $phone, array $options = []): Response
    {
        return $this->getContact($phone, $options);
    }

    // ---- media -------------------------------------------------------------

    /**
     * Unified media-upload helper. Wapio splits this internally into
     * `uploadMediaGrant` (presign URL flow) and `directUpload` (base64
     * inline). This alias routes by payload shape: if `content_base64`
     * is set, it goes through `directUpload`; otherwise it goes through
     * `uploadMediaGrant`.
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function uploadMediaFile(array $payload, array $options = []): Response
    {
        if (isset($payload['content_base64'])) {
            return $this->directUpload($payload, $options);
        }
        return $this->uploadMediaGrant($payload, $options);
    }

    /** @param array<string,mixed> $payload @param array<string,mixed> $options */
    public function decryptMediaFile(array $payload, array $options = []): Response
    {
        return $this->decryptMedia($payload, $options);
    }

    // ---- messages ----------------------------------------------------------

    /**
     * Send a text with @mentions. Just a thin pass-through to `sendText`
     * with the `mentions` array already populated.
     *
     * @param array{to:string,text:string,mentions:array<int,string>} $payload
     * @param array<string,mixed> $options
     */
    public function sendMessageWithMentions(array $payload, array $options = []): Response
    {
        return $this->sendText($payload, $options);
    }

    /**
     * Send a text that quotes another message.
     *
     * @param array{to:string,text:string,quoted_message_id:string} $payload
     * @param array<string,mixed> $options
     */
    public function sendQuotedMessage(array $payload, array $options = []): Response
    {
        return $this->sendText($payload, $options);
    }
}
