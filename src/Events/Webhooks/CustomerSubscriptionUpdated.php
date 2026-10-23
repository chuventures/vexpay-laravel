<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `customer.subscription.updated` */
final class CustomerSubscriptionUpdated extends VexPayWebhookEvent
{
}
