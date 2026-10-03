<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `conversion.canceled` */
final class ConversionCanceled extends VexPayWebhookEvent
{
}
