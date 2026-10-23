<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `customer.subscription.trial_will_end` */
final class CustomerSubscriptionTrialWillEnd extends VexPayWebhookEvent
{
}
