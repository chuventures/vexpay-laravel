<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Billing;

/** A payment was reversed — undo fulfilment if needed. */
final class PaymentReversed extends BillingEvent
{
}
