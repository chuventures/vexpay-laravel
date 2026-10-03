<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use VexPay\Laravel\Models\Payment;

/**
 * A local payment changed status. Fires exactly once per transition, however many times
 * VEXPay delivers the underlying webhook — safe to fulfil orders from.
 */
abstract class BillingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    /**
     * The model that started the checkout (e.g. the Order).
     */
    public function billable(): ?Model
    {
        return $this->payment->billable;
    }
}
