<?php

declare(strict_types=1);

namespace Wapio\Resources;

use Wapio\Http\Response;

trait MediaTrait
{
    /**
     * Request a presigned upload grant for sending media.
     *
     * Returns a `media_handle` you can pass back to sendImage/sendVideo/etc
     * as the media reference, plus the URL and headers to PUT the bytes to.
     *
     * @param array{kind:string,mime_type:string,size_bytes:int} $payload
     * @param array<string,mixed> $options
     */
    public function uploadMediaGrant(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/media',
            body: $payload,
            options: $options,
        );
    }

    /** @param array<string,mixed> $options */
    public function getMediaDownloadUrl(string $mediaId, array $options = []): Response
    {
        return $this->http->request(
            method: 'GET',
            path: '/v1/media/' . rawurlencode($mediaId) . '/download-url',
            options: $options,
        );
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    public function decryptMedia(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/v1/media/decrypt',
            body: $payload,
            options: $options,
        );
    }

    /**
     * Send media bytes (base64) in one round-trip. Prefer `uploadMediaGrant`
     * for files larger than a few MB.
     *
     * @param array{content_base64:string,mime_type:string,file_name?:string,file_length?:int} $payload
     * @param array<string,mixed> $options
     */
    public function directUpload(array $payload, array $options = []): Response
    {
        return $this->http->request(
            method: 'POST',
            path: '/api/upload',
            body: $payload,
            options: $options,
        );
    }
}
