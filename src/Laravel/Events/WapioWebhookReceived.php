<?php

declare(strict_types=1);

namespace Wapio\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wapio\Webhook\WebhookEvent;

/**
 * Generic Laravel event fired by the Wapio webhook controller after a
 * successful HMAC-SHA256 signature verification.
 *
 * Listen with::
 *
 *     // app/Providers/EventServiceProvider.php
 *     protected $listen = [
 *         WapioWebhookReceived::class => [HandleIncomingWhatsApp::class],
 *     ];
 *
 *     // Or:
 *     Event::listen(function (WapioWebhookReceived $event) {
 *         if ($event->event->eventType === 'message_received') { … }
 *     });
 */
class WapioWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly WebhookEvent $event)
    {
    }
}
