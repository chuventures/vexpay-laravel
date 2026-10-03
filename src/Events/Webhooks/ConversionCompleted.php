<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `conversion.completed` */
final class ConversionCompleted extends VexPayWebhookEvent
{
}
