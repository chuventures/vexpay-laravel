<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `payment_method.attached` */
final class PaymentMethodAttached extends VexPayWebhookEvent
{
}
