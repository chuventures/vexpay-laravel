<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VexPay\Webhook\WebhookEvent;

/**
 * Base of every webhook-driven event. `$webhook` is the verified delivery
 * (`->event`, `->data`, `->timestamp`). Fires once per delivery attempt — VEXPay retries
 * and resends, so listeners must be idempotent.
 */
abstract class VexPayWebhookEvent
{
    use Dispatchable;

    public function __construct(public readonly WebhookEvent $webhook)
    {
    }
}
