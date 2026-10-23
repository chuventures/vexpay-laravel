<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `invoice.overdue` */
final class InvoiceOverdue extends VexPayWebhookEvent
{
}
