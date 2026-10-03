<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `tenant.api_key.created` */
final class TenantApiKeyCreated extends VexPayWebhookEvent
{
}
