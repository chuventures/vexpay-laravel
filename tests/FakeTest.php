<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use VexPay\Laravel\Events\Billing\PaymentSucceeded;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Models\Payment;
use VexPay\Laravel\Tests\Fixtures\Order;
use VexPay\VexPayClient;

final class FakeTest extends TestCase
{
    public function testCheckoutThenSimulatedWebhookEndToEnd(): void
    {
        $fake = VexPay::fake();
        Event::fake([PaymentSucceeded::class]);
        Route::post('/orders/{id}/pay', static fn (string $id) => Order::findOrFail($id)->checkout(25));
        $order = Order::create(['number' => '1042']);

        $this->post("/orders/{$order->id}/pay")->assertRedirect();
        VexPay::assertCheckoutCreated(25);

        $payment = $order->vexpayPayments()->sole();
        $this->postVexPayPaymentWebhook($payment)->assertNoContent();

        self::assertSame(Payment::STATUS_COMPLETED, $payment->refresh()->status);
        Event::assertDispatched(PaymentSucceeded::class);
        self::assertCount(1, $fake->recorded());
    }

    public function testFakeReplacesTheContainerClient(): void
    {
        $fake = VexPay::fake();

        self::assertSame($fake->client(), $this->app->make(VexPayClient::class));
        VexPay::assertNothingSent();
    }

    public function testAssertionsFailWhenNothingMatches(): void
    {
        VexPay::fake();
        Order::create(['number' => '1'])->checkout(10);

        VexPay::assertNotSent('Payouts_create');
        $this->expectException(AssertionFailedError::class);
        VexPay::assertCheckoutCreated(99);
    }
}
