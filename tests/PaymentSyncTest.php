<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use VexPay\Laravel\Events\Billing\PaymentFailed;
use VexPay\Laravel\Events\Billing\PaymentReversed;
use VexPay\Laravel\Events\Billing\PaymentSucceeded;
use VexPay\Laravel\Events\WebhookReceived;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Models\Payment;
use VexPay\Laravel\Sync\PaymentSynchronizer;
use VexPay\Laravel\Testing\SignedWebhook;
use VexPay\Laravel\Tests\Fixtures\Order;
use VexPay\Webhook;

final class PaymentSyncTest extends TestCase
{
    private Order $order;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        VexPay::fake();
        $this->order = Order::create(['number' => '1042']);
        $this->payment = $this->order->checkout(25)->payment;
    }

    public function testCompletedPaymentIsSyncedAndFiresOnce(): void
    {
        Event::fake([PaymentSucceeded::class]);

        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', [
            'paymentId' => 'pay_77',
            'vesAmount' => 9125.5,
            'bcvRate' => 365.02,
            'method' => 'C2P',
        ])->assertNoContent();

        $this->payment->refresh();
        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->status);
        self::assertSame('pay_77', $this->payment->payment_id);
        self::assertSame('9125.50', $this->payment->amount_ves);
        self::assertSame('C2P', $this->payment->method);
        Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
        Event::assertDispatched(PaymentSucceeded::class, fn (PaymentSucceeded $e) => $e->billable()?->is($this->order) === true);
    }

    public function testCopPaidCheckoutIsSynced(): void
    {
        Event::fake([PaymentSucceeded::class]);

        // A Billable checkout paid in Colombian pesos: the COP payload has no VES fields.
        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', [
            'paymentId' => 'cop_77',
            'method' => 'COP',
            'currency' => 'COP',
            'status' => 'completed',
            'channel' => 'nequi',
            'amountCop' => 82500,
            'amountUsd' => '25.00',
            'copRate' => '3300.000000',
            'vesAmount' => null,
            'bcvRate' => null,
        ])->assertNoContent();

        $this->payment->refresh();
        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->status);
        self::assertSame('cop_77', $this->payment->payment_id);
        self::assertSame('COP', $this->payment->method);
        self::assertNull($this->payment->amount_ves);
        Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
    }

    public function testDuplicateDeliveryChangesNothingAndDoesNotRefire(): void
    {
        Event::fake([PaymentSucceeded::class]);

        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77'])->assertNoContent();
        $updatedAt = $this->payment->refresh()->updated_at;
        $this->travel(1)->minutes();
        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77'])->assertNoContent();

        Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
        $this->payment->refresh();
        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->status);
        self::assertTrue($updatedAt->equalTo($this->payment->updated_at));
    }

    public function testConcurrentDuplicatesFireOnce(): void
    {
        Event::fake([PaymentSucceeded::class]);
        $event = Webhook::constructEvent(
            ($signed = SignedWebhook::forPayment($this->payment, 'payment.completed', ['paymentId' => 'pay_77']))->body,
            $signed->headers[Webhook::SIGNATURE_HEADER],
            self::WEBHOOK_SECRET,
        );

        // Two workers that both loaded the row while it was still PENDING.
        $sync = new PaymentSynchronizer();
        $sync->sync($event);
        $sync->sync($event);

        Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
    }

    public function testLatePendingDoesNotRegressACompletedPayment(): void
    {
        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77']);
        $this->postVexPayPaymentWebhook($this->payment, 'payment.pending', ['paymentId' => 'pay_77'])->assertNoContent();

        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->refresh()->status);
    }

    public function testFailedCannotOverwriteCompleted(): void
    {
        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77']);
        $this->postVexPayPaymentWebhook($this->payment, 'payment.failed', ['failureCode' => 'timeout']);

        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->refresh()->status);
    }

    public function testReversalAfterCompletion(): void
    {
        Event::fake([PaymentSucceeded::class, PaymentReversed::class]);

        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77']);
        $this->postVexPayPaymentWebhook($this->payment, 'payment.reversed', ['paymentId' => 'pay_77']);

        self::assertSame(Payment::STATUS_REVERSED, $this->payment->refresh()->status);
        Event::assertDispatchedTimes(PaymentReversed::class, 1);
    }

    public function testReversalThatOvertakesCompletionStillWins(): void
    {
        $this->postVexPayPaymentWebhook($this->payment, 'payment.reversed', ['paymentId' => 'pay_77']);
        $this->postVexPayPaymentWebhook($this->payment, 'payment.completed', ['paymentId' => 'pay_77']);

        self::assertSame(Payment::STATUS_REVERSED, $this->payment->refresh()->status);
    }

    public function testFailureRecordsTheCode(): void
    {
        Event::fake([PaymentFailed::class]);

        $this->postVexPayPaymentWebhook($this->payment, 'payment.failed', ['paymentId' => 'pay_9', 'failureCode' => 'insufficient_funds']);

        $this->payment->refresh();
        self::assertSame(Payment::STATUS_FAILED, $this->payment->status);
        self::assertSame('insufficient_funds', $this->payment->failure_code);
        Event::assertDispatched(PaymentFailed::class);
    }

    public function testMatchesByReferenceWhenThereIsNoSession(): void
    {
        $this->postVexPayWebhook('payment.completed', [
            'paymentId' => 'pay_5',
            'status' => 'COMPLETED',
            'externalRef' => $this->payment->reference,
        ])->assertNoContent();

        self::assertSame(Payment::STATUS_COMPLETED, $this->payment->refresh()->status);
    }

    public function testUnmatchedDeliveriesStillDispatchAndDoNotError(): void
    {
        Event::fake([WebhookReceived::class, PaymentSucceeded::class]);

        $this->postVexPayWebhook('payment.completed', ['paymentId' => 'pay_other', 'status' => 'COMPLETED', 'externalRef' => 'woo-123'])
            ->assertNoContent();

        Event::assertDispatched(WebhookReceived::class);
        Event::assertNotDispatched(PaymentSucceeded::class);
        self::assertSame(Payment::STATUS_PENDING, $this->payment->refresh()->status);
    }
}
