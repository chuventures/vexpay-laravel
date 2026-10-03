<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events\Webhooks;

use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\Events\VexPayWebhookEvent;
use VexPay\Laravel\Sync\MerchantOwners;

abstract class MerchantWebhookEvent extends VexPayWebhookEvent
{
    public function merchantId(): ?string
    {
        $id = $this->webhook->data['merchantId'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * The Eloquent model (using HasVexPayMerchant) this merchant was created from, if any.
     */
    public function owner(): ?Model
    {
        $ref = $this->webhook->data['externalRef'] ?? null;

        return MerchantOwners::resolve($this->merchantId(), is_string($ref) ? $ref : null);
    }
}
