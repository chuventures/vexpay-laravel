<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events;

/**
 * Dispatched after the package has synced local state (e.g. `vexpay_payments`) for a delivery.
 */
final class WebhookHandled extends VexPayWebhookEvent
{
}
