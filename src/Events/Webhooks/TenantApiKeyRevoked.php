<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `tenant.api_key.revoked` */
final class TenantApiKeyRevoked extends VexPayWebhookEvent
{
}
