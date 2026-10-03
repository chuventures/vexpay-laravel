<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Billing;

/** The payment failed; `$payment->failure_code` says why. */
final class PaymentFailed extends BillingEvent
{
}
