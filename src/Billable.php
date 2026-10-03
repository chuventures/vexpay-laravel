<?php

declare(strict_types=1);

namespace VexPay\Laravel;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use VexPay\Laravel\Models\Payment;
use VexPay\VexPayClient;

/**
 * One-time VEXPay checkouts for any Eloquent model (an Order, an Invoice, a User…).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Billable
{
    /**
     * Create a checkout session for `$amountUsd` and record it locally as pending.
     *
     * @param array<string, mixed> $options checkout session fields: description, successUrl, cancelUrl,
     *                                      methods, allowedOrigins, metadata, expiresInMinutes, reference
     */
    public function checkout(float|int|string $amountUsd, array $options = []): Checkout
    {
        $reference = (string) ($options['reference'] ?? Str::ulid());
        $metadata = array_merge((array) ($options['metadata'] ?? []), [
            'billable_type' => $this->getMorphClass(),
            'billable_id' => (string) $this->getKey(),
        ]);
        $amount = round((float) $amountUsd, 2);

        $session = app(VexPayClient::class)->checkout->sessions->create(
            array_merge($options, ['amountUsd' => $amount, 'reference' => $reference, 'metadata' => $metadata]),
            ['idempotency_key' => 'checkout-' . $reference],
        );

        /** @var Payment $payment */
        $payment = $this->vexpayPayments()->create([
            'checkout_session_id' => $session->id,
            'reference' => $reference,
            'amount_usd' => $amount,
            'status' => Payment::STATUS_PENDING,
            'livemode' => $session->livemode,
            'metadata' => $metadata,
        ]);

        return new Checkout($session, $payment);
    }

    /**
     * Local payment records for this model, newest first.
     */
    public function vexpayPayments(): MorphMany
    {
        return $this->morphMany(config('vexpay.payments.model', Payment::class), 'billable')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
