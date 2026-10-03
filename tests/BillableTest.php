<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Models\Payment;
use VexPay\Laravel\Tests\Fixtures\Order;
use VexPay\Testing\RecordedRequest;

final class BillableTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('/orders/{order}/pay', static fn (Order $order) => $order->checkout(25.00, [
            'description' => 'Pedido #' . $order->number,
            'successUrl' => 'https://shop.example/gracias',
            'cancelUrl' => 'https://shop.example/carrito',
            'metadata' => ['orderNumber' => $order->number],
        ]))->middleware(SubstituteBindings::class);
    }

    public function testCheckoutRedirectsToHostedPageAndStoresPendingPayment(): void
    {
        $fake = VexPay::fake(['CheckoutSessions_create' => ['url' => 'https://pay.vexwallet.co/c/cs_1', 'id' => 'cs_1']]);
        $order = Order::create(['number' => '1042']);

        $this->post("/orders/{$order->id}/pay")->assertRedirect('https://pay.vexwallet.co/c/cs_1')->assertStatus(303);

        VexPay::assertCheckoutCreated(25.00);
        VexPay::assertCheckoutCreated(static fn (array $body) => $body['successUrl'] === 'https://shop.example/gracias'
            && $body['metadata'] === ['orderNumber' => '1042', 'billable_type' => Order::class, 'billable_id' => (string) $order->id]);

        $payment = $order->vexpayPayments()->sole();
        self::assertSame('cs_1', $payment->checkout_session_id);
        self::assertSame(Payment::STATUS_PENDING, $payment->status);
        self::assertSame('25.00', $payment->amount_usd);
        self::assertTrue($payment->billable->is($order));
        $sent = $fake->recorded('CheckoutSessions_create')[0];
        self::assertSame($payment->reference, $sent->body['reference']);
        self::assertSame('checkout-' . $payment->reference, $sent->header('Idempotency-Key'));
    }

    public function testJsonRequestsGetTheEmbedFields(): void
    {
        VexPay::fake(['CheckoutSessions_create' => ['clientSecret' => 'cs_secret_123']]);
        $order = Order::create(['number' => '7']);

        $this->postJson("/orders/{$order->id}/pay")
            ->assertOk()
            ->assertJsonStructure(['id', 'url'])
            ->assertJson(['clientSecret' => 'cs_secret_123']);
    }

    public function testCallerReferenceIsKept(): void
    {
        VexPay::fake();
        $order = Order::create(['number' => '8']);

        $checkout = $order->checkout('10.5', ['reference' => 'order-8']);

        self::assertSame('order-8', $checkout->payment->reference);
        VexPay::assertSent('CheckoutSessions_create', static fn (array $body, RecordedRequest $r) => $body['reference'] === 'order-8'
            && $body['amountUsd'] === 10.5);
    }

    public function testPaymentsAreListedNewestFirst(): void
    {
        VexPay::fake();
        $order = Order::create(['number' => '9']);

        $first = $order->checkout(5)->payment;
        $this->travel(1)->minutes();
        $second = $order->checkout(6)->payment;

        self::assertSame([$second->id, $first->id], $order->vexpayPayments->pluck('id')->all());
    }

    public function testNoHttpLeavesTheProcessUnderTheFake(): void
    {
        VexPay::fake();
        Route::get('/quote', static fn () => ['ves' => VexPay::quotes()->retrieve(['usdAmount' => 10])->vesAmount]);

        $this->getJson('/quote')->assertOk();

        VexPay::assertSent('Payments_getQuote');
    }
}
