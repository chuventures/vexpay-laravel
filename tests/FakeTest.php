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

    public function testBillingResourcesThroughTheFacade(): void
    {
        VexPay::fake();

        $customer = VexPay::customers()->create(['email' => 'ana@example.com']);
        VexPay::subscriptions()->create(['customer' => $customer->id, 'price' => 'price_1']);

        VexPay::assertSent('Customers_create', static fn (array $body) => $body['email'] === 'ana@example.com');
        VexPay::assertSent('Subscriptions_create');
    }

    public function testBalanceTopupsThroughTheFacade(): void
    {
        VexPay::fake()->stub('Topups_create', ['id' => 'tp_1', 'amountVes' => '100000.00']);

        $topup = VexPay::balance()->topups->create([
            'amountVes' => '100000.00',
            'payerPhone' => '04121234567',
            'payerCedula' => 'V12345678',
            'sourceNote' => 'Ingresos publicidad quincena 2',
        ]);
        self::assertSame('tp_1', $topup->id);
        VexPay::balance()->topups->list(['limit' => 5]);
        VexPay::balance()->topups->instant('tp_1');

        VexPay::assertSent('Topups_create', static fn (array $body) => $body['sourceNote'] === 'Ingresos publicidad quincena 2');
        VexPay::assertSent('Topups_list');
        VexPay::assertSent('Topups_instant');
    }

    public function testSimulateDepositThroughTheFacade(): void
    {
        VexPay::fake()->stub('Crypto_simulateDeposit', ['paymentId' => 'pay_1', 'simulated' => true, 'livemode' => false]);

        $deposit = VexPay::crypto()->simulateDeposit(['customerRef' => 'user_123', 'network' => 'TRC20', 'amount' => '40.00']);
        self::assertSame('pay_1', $deposit->paymentId);

        VexPay::assertSent('Crypto_simulateDeposit', static fn (array $body) => $body['customerRef'] === 'user_123' && $body['amount'] === '40.00');
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
