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
     * The seller model (using HasVexPayMerchant) that received the payout, if any.
     */
    public function owner(): ?Model
    {
        return MerchantOwners::resolve($this->merchantId(), null);
    }
}
