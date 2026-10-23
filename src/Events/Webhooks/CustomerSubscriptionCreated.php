<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `customer.subscription.created` */
final class CustomerSubscriptionCreated extends VexPayWebhookEvent
{
}
