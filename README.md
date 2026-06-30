# wapio/wapio

Official PHP / Laravel SDK for [Wapio](https://www.wapio.io). Send WhatsApp messages, manage WhatsApp sessions, contacts, groups, channels, and decrypt inbound media.

```sh
composer require wapio/wapio
```

PHP 8.1+ is required. Laravel support is optional.

## Features

- Framework-agnostic PHP client with optional Laravel service provider and facade.
- Text, image, video, audio, sticker, document, contact, location, poll, group, and channel message helpers.
- Session lifecycle: create, list, update, delete, connect, disconnect, QR, status, and key rotation.
- Contact management: list, create/update, details, profile picture, block, and unblock.
- Group management: list, metadata, participants, add/remove, promote/demote, invite links, leave, and settings.
- Message lifecycle: info, edit, delete, resend failed, mark as read, presence, and logs.
- Webhook signature verification and Laravel event helper.
- Inbound encrypted media decrypt helper.
- Rate-limit metadata and request IDs on every response.
- Automatic idempotency keys for message sends.

## Quickstart

```php
<?php

use Wapio\Wapio;

$wapio = new Wapio(apiKey: 'bps_sk_...');

$result = $wapio->sendText([
    'to' => '+15551234567',
    'text' => 'Hello from Wapio',
]);

echo $result->data['msgId'];
```

## Laravel

The package self-registers via Composer auto-discovery.

```sh
composer require wapio/wapio
php artisan vendor:publish --tag=wapio-config
```

`.env`:

```env
WAPIO_API_KEY=bps_sk_...
WAPIO_PAT=bps_pat_...
WAPIO_BASE_URL=https://api.wapio.io
```

Use dependency injection:

```php
use Wapio\Wapio;

class OrderShippedController
{
    public function __invoke(Wapio $wapio): void
    {
        $wapio->sendText([
            'to' => '+15551234567',
            'text' => 'Your order has shipped',
        ]);
    }
}
```

Or use the facade:

```php
use WapioApi;

WapioApi::sendText(['to' => '+15551234567', 'text' => 'Hi']);
```

## Authentication

| Token | Format | Use |
| --- | --- | --- |
| Personal Access Token | `bps_pat_...` | Create, update, delete, connect, disconnect, and rotate WhatsApp sessions. |
| Session key | `bps_sk_...` | Send messages and read resources for one connected WhatsApp session. |

```php
$wapio = new Wapio(
    apiKey: 'bps_sk_...',
    personalAccessToken: 'bps_pat_...',
);
```

## Messages

```php
$wapio->sendText(['to' => '+15551234567', 'text' => 'Hi']);
$wapio->sendImage(['to' => '+15551234567', 'imageUrl' => 'https://example.com/image.jpg', 'caption' => 'Image']);
$wapio->sendVideo(['to' => '+15551234567', 'videoUrl' => 'https://example.com/video.mp4']);
$wapio->sendDocument([
    'to' => '+15551234567',
    'documentUrl' => 'https://example.com/file.pdf',
    'file_name' => 'file.pdf',
]);
$wapio->sendAudio(['to' => '+15551234567', 'audioUrl' => 'https://example.com/audio.ogg', 'voice' => true]);
$wapio->sendSticker(['to' => '+15551234567', 'stickerUrl' => 'https://example.com/sticker.webp']);
$wapio->sendContact([
    'to' => '+15551234567',
    'contact' => ['name' => 'M', 'phone' => '+15559876543'],
]);
$wapio->sendLocation([
    'to' => '+15551234567',
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'name' => 'New York',
]);
$wapio->sendPoll([
    'to' => '+15551234567',
    'question' => 'Choose a delivery slot',
    'options' => ['Morning', 'Afternoon'],
]);
```

Pass your own idempotency key when you need retry deduplication across processes:

```php
$wapio->sendText(
    ['to' => '+15551234567', 'text' => 'Order #42 shipped'],
    ['idempotency_key' => 'order:42:shipped']
);
```

### Channels

Use the WhatsApp Channel ID ending in `@newsletter`.

```php
$wapio->sendChannelMessage(
    '120363428122592568@newsletter',
    "This week's release is live"
);
```

### Message lifecycle

```php
$wapio->getMessageInfo('bps_msg_...');
$wapio->editMessage('bps_msg_...', 'Corrected text');
$wapio->deleteMessage('bps_msg_...');
$wapio->resendFailedMessage('bps_msg_...');
$wapio->markMessageAsRead(['bps_msg_...'], '15551234567@s.whatsapp.net');
$wapio->sendPresenceUpdate(['to' => '15551234567@s.whatsapp.net', 'presence' => 'composing']);
```

Use the generic `send` method for advanced payloads supported by Wapio:

```php
$wapio->send([
    'to' => '+15551234567',
    'text' => 'Replying to your earlier message',
    'quoted_message_id' => 'bps_msg_parent',
]);

$wapio->sendViewOnceMessage([
    'to' => '+15551234567',
    'kind' => 'image',
    'imageUrl' => 'https://example.com/private.jpg',
    'caption' => 'View once',
]);
```

## Sessions

```php
$created = $wapio->createSession(['label' => 'Support line']);
$sessionId = $created->data['session']['session_id'];
$rawSessionKey = $created->data['session_api_key']['raw'] ?? null;

$wapio->listSessions(['status' => 'connected', 'limit' => 50]);
$wapio->getSession($sessionId);

$wapio->updateSession($sessionId, ['label' => 'Support EU']);
$wapio->getSessionQrCode($sessionId);
$wapio->getSessionStatus();
$wapio->connectSession($sessionId);
$wapio->disconnectSession($sessionId);

$rotated = $wapio->regenerateApiKey($sessionId);
echo $rotated->data['session_api_key']['raw'];

$wapio->deleteSession($sessionId);
```

Store `session_api_key.raw` when it is returned. It is shown once.

## Contacts

```php
$wapio->getContacts();
$wapio->createOrUpdateContact(['phone' => '+15551234567', 'name' => 'M']);
$wapio->getContact('+15551234567');
$wapio->getContactProfilePicture('+15551234567');
$wapio->blockContact('+15551234567');
$wapio->unblockContact('+15551234567');
```

## Groups

```php
$group = $wapio->createGroup([
    'subject' => 'Customer updates',
    'participants' => ['15551234567@s.whatsapp.net'],
]);
$groupJid = $group->data['group_jid'];

$wapio->getGroups();
$wapio->getGroupMetadata($groupJid);
$wapio->getGroupParticipants($groupJid);
$wapio->getGroupProfilePicture($groupJid);
$wapio->addGroupParticipants($groupJid, ['15559876543@s.whatsapp.net']);
$wapio->removeGroupParticipants($groupJid, ['15559876543@s.whatsapp.net']);
$wapio->promoteGroupParticipants($groupJid, ['15559876543@s.whatsapp.net']);
$wapio->demoteGroupParticipants($groupJid, ['15559876543@s.whatsapp.net']);
$wapio->updateGroupSettings($groupJid, ['announce' => true]);
$wapio->getGroupInviteLink($groupJid);
$wapio->getGroupInviteMetadata('INVITE_CODE');
$wapio->acceptGroupInvite('INVITE_CODE');
$wapio->leaveGroup($groupJid);

$wapio->sendGroupMessage($groupJid, 'Hello group', ['15559876543@s.whatsapp.net']);
```

## Media

For outbound media, pass a public HTTPS URL to `sendImage`, `sendVideo`, or `sendDocument`.

Use `decryptMedia` for inbound encrypted WhatsApp media payloads:

```php
$media = $wapio->decryptMedia([
    'mediaKey' => '...',
    'directPath' => '...',
    'url' => 'https://mmg.whatsapp.net/...',
    'type' => 'image',
]);
```

## Utilities

```php
$wapio->getUser();
$wapio->onWhatsapp('+15551234567');
$wapio->getMessageLogs('bps_sess_ws_...', ['limit' => 50]);
$wapio->getSessionLogs('bps_sess_ws_...', ['limit' => 50]);
```

## Webhooks

Verify the raw request body with your session webhook signing secret before trusting the payload:

```php
use Wapio\Webhook;

$rawBody = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

$event = Webhook::constructEvent(
    secret: 'whsec_...',
    signatureHeader: $signature,
    rawBody: $rawBody,
);
```

In Laravel, dispatch a typed event after verification:

```php
use Wapio\Webhook;

$event = Webhook::constructEvent($secret, $request->header('X-Webhook-Signature'), $request->getContent());

Webhook::dispatchLaravelEvent($event, fn ($laravelEvent) => event($laravelEvent));
```

## Errors

SDK methods throw `WapioApiException` for API errors and `WapioConfigException` for missing credentials before a request is sent.
