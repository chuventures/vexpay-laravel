<?php

declare(strict_types=1);

namespace VexPay\Laravel\Sync;

use VexPay\Laravel\Events\Billing;
use VexPay\Laravel\Models\Payment;
use VexPay\Webhook\PaymentWebhookData;
use VexPay\Webhook\WebhookEvent;

/**
 * Applies payment.* webhooks to `vexpay_payments` as a state machine. VEXPay's delivery id
 * changes per attempt, so idempotency comes from the transitions themselves: each one is a
 * conditional update on the allowed predecessor states, and billing events fire only when that
 * update changed a row — once per transition even under concurrent duplicate deliveries.
 */
final class PaymentSynchronizer
{
    /** Target status => statuses it may be reached from. Terminal statuses never regress. */
    private const TRANSITIONS = [
        Payment::STATUS_COMPLETED => [Payment::STATUS_PENDING],
        Payment::STATUS_FAILED => [Payment::STATUS_PENDING],
        Payment::STATUS_CANCELED => [Payment::STATUS_PENDING],
        // Accepted from PENDING too, so a reversal that overtakes its completion still lands.
        Payment::STATUS_REVERSED => [Payment::STATUS_PENDING, Payment::STATUS_COMPLETED],
    ];

    private const EVENT_STATUS = [
        'payment.pending' => Payment::STATUS_PENDING,
        'payment.completed' => Payment::STATUS_COMPLETED,
        'payment.failed' => Payment::STATUS_FAILED,
        'payment.canceled' => Payment::STATUS_CANCELED,
        'payment.reversed' => Payment::STATUS_REVERSED,
    ];

    private const BILLING_EVENTS = [
        Payment::STATUS_COMPLETED => Billing\PaymentSucceeded::class,
        Payment::STATUS_FAILED => Billing\PaymentFailed::class,
        Payment::STATUS_CANCELED => Billing\PaymentCanceled::class,
        Payment::STATUS_REVERSED => Billing\PaymentReversed::class,
    ];

    /**
     * Returns the synced local payment, or null when the delivery belongs to no local record.
     */
    public function sync(WebhookEvent $event): ?Payment
    {
        $data = $event->payment();
        if ($data === null) {
            return null;
        }
        $payment = $this->find($data);
        if ($payment === null) {
            return null;
        }

        $target = self::EVENT_STATUS[$event->event] ?? strtoupper($data->status);
        $details = array_filter([
            'payment_id' => $data->paymentId !== '' ? $data->paymentId : null,
            'amount_ves' => $data->vesAmount,
            'bcv_rate' => $data->bcvRate,
            'method' => $data->method,
            'failure_code' => $data->failureCode,
            'livemode' => $data->livemode,
        ], static fn ($value) => $value !== null);

        $changed = 0;
        if (isset(self::TRANSITIONS[$target])) {
            $changed = $this->query()
                ->whereKey($payment->getKey())
                ->whereIn('status', self::TRANSITIONS[$target])
                ->update(['status' => $target] + $details);
        }
        if ($changed === 0) {
            // Repeat or stale delivery: only fill details still missing, so a duplicate changes nothing.
            $missing = array_filter($details, static fn ($value, $column) => $payment->getAttribute($column) === null, ARRAY_FILTER_USE_BOTH);
            if ($missing !== []) {
                $this->query()->whereKey($payment->getKey())->update($missing);
            }
        }

        $payment->refresh();
        if ($changed > 0 && isset(self::BILLING_EVENTS[$target])) {
            $class = self::BILLING_EVENTS[$target];
            event(new $class($payment));
        }

        return $payment;
    }

    /**
     * Matches by checkout session id, then reference, then payment id.
     */
    public function find(PaymentWebhookData $data): ?Payment
    {
        $sessionId = $data->checkoutSession?->id;
        if ($sessionId !== null && $sessionId !== '') {
            $match = $this->query()->where('checkout_session_id', $sessionId)->first();
            if ($match !== null) {
                return $match;
            }
        }
        $reference = $data->checkoutSession?->reference ?? $data->externalRef;
        if ($reference !== null && $reference !== '') {
            $match = $this->query()->where('reference', $reference)->first();
            if ($match !== null) {
                return $match;
            }
        }
        if ($data->paymentId !== '') {
            return $this->query()->where('payment_id', $data->paymentId)->first();
        }

        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    private function query(): \Illuminate\Database\Eloquent\Builder
    {
        /** @var class-string<Payment> $model */
        $model = config('vexpay.payments.model', Payment::class);

        return $model::query();
    }
}
