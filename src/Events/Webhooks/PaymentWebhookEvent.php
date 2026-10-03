<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;
use VexPay\Laravel\Models\Payment;
use VexPay\Laravel\Sync\PaymentSynchronizer;
use VexPay\Webhook\PaymentWebhookData;

abstract class PaymentWebhookEvent extends VexPayWebhookEvent
{
    public function payment(): PaymentWebhookData
    {
        return PaymentWebhookData::fromArray($this->webhook->data);
    }

    /**
     * The `vexpay_payments` row this payment belongs to, if it came from a Billable checkout.
     * Dispatched before local sync — read it in a WebhookHandled listener for the synced status.
     */
    public function localPayment(): ?Payment
    {
        return app(PaymentSynchronizer::class)->find($this->payment());
    }
}
