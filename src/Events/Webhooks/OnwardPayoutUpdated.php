<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/** `onward_payout.updated` */
final class OnwardPayoutUpdated extends VexPayWebhookEvent
{
}
