<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Billing;

/** The payment was canceled (superseded or expired) before completing. */
final class PaymentCanceled extends BillingEvent
{
}
