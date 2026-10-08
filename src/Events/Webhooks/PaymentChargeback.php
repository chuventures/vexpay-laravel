<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use VexPay\Laravel\Events\VexPayWebhookEvent;

/**
 * `payment.chargeback` — a bank chargeback on a card payment. `$this->webhook->data` carries `chargebackId`,
 * `paymentId`, `amountVes`, `feeVes`, `reason`, `status` and `ledgerEntryIds` (match them with
 * `VexPay::balance()->transactions->list(['paymentId' => …])`).
 */
final class PaymentChargeback extends VexPayWebhookEvent
{
    public function paymentId(): string
    {
        return (string) ($this->webhook->data['paymentId'] ?? '');
    }

    public function chargebackId(): string
    {
        return (string) ($this->webhook->data['chargebackId'] ?? '');
    }
}
