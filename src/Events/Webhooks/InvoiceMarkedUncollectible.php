<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `invoice.marked_uncollectible` */
final class InvoiceMarkedUncollectible extends VexPayWebhookEvent
{
}
