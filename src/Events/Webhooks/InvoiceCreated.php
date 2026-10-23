<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `invoice.created` */
final class InvoiceCreated extends VexPayWebhookEvent
{
}
