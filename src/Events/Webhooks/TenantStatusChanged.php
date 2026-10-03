<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `tenant.status_changed` */
final class TenantStatusChanged extends VexPayWebhookEvent
{
}
