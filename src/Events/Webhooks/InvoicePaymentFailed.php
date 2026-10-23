<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `invoice.payment_failed` */
final class InvoicePaymentFailed extends VexPayWebhookEvent
{
}
