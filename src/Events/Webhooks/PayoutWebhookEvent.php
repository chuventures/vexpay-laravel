<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\Events\VexPayWebhookEvent;
use VexPay\Laravel\Sync\MerchantOwners;

abstract class PayoutWebhookEvent extends VexPayWebhookEvent
{
    public function payoutId(): ?string
    {
        $id = $this->webhook->data['payoutId'] ?? null;

        return is_string($id) ? $id : null;
    }

    public function merchantId(): ?string
    {
        $id = $this->webhook->data['merchantId'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * True when your platform balance paid the withdrawal fee (`feePayer: "tenant"`, disbursements):
     * the seller was debited only `monto`, and `feeVes()` is what your balance paid.
     */
    public function feePaidByTenant(): bool
    {
        return ($this->webhook->data['feePayer'] ?? null) === 'tenant';
    }

    /**
     * Merchant-balance payouts: the withdrawal fee in VES ("0.00" when none), or null when absent.
     */
    public function feeVes(): ?string
    {
        $fee = $this->webhook->data['feeVes'] ?? null;

        return is_string($fee) ? $fee : null;
    }

    /**
     * The seller model (using HasVexPayMerchant) that received the payout, if any.
     */
    public function owner(): ?Model
    {
        return MerchantOwners::resolve($this->merchantId(), null);
    }
}
