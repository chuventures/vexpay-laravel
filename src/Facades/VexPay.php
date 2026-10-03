<?php

declare(strict_types=1);

namespace VexPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use VexPay\Laravel\Testing\VexPayFake;
use VexPay\VexPayClient;

/**
 * @method static \VexPay\Resource\Payments payments()
 * @method static \VexPay\Resource\Checkout checkout()
 * @method static \VexPay\Resource\Merchants merchants()
 * @method static \VexPay\Resource\Payouts payouts()
 * @method static \VexPay\Resource\Products products()
 * @method static \VexPay\Resource\PaymentLinks paymentLinks()
 * @method static \VexPay\Resource\Banks banks()
 * @method static \VexPay\Resource\Quotes quotes()
 * @method static \VexPay\Resource\Balance balance()
 * @method static \VexPay\Resource\TenantPayoutAccount tenantPayoutAccount()
 * @method static \VexPay\Resource\WebhookEndpoints webhookEndpoints()
 * @method static \VexPay\Resource\Crypto crypto()
 * @method static \VexPay\Resource\Conversions conversions()
 * @method static void assertSent(string $operationId, ?callable $callback = null)
 * @method static void assertNotSent(string $operationId, ?callable $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertCheckoutCreated(float|int|callable|null $amountOrCallback = null)
 * @method static void assertPayoutCreated(?callable $callback = null)
 * @method static void assertMerchantCreated(?callable $callback = null)
 *
 * @see VexPayClient
 * @see VexPayFake
 */
final class VexPay extends Facade
{
    /**
     * Swap the VEXPay client for an in-memory fake: no request leaves the process, every
     * operation answers with a valid response, and calls can be asserted.
     *
     * @param array<string, mixed> $stubs responses keyed by operationId, e.g. ['Payouts_create' => ['status' => 'completed']]
     */
    public static function fake(array $stubs = []): VexPayFake
    {
        $fake = new VexPayFake($stubs);
        static::swap($fake);
        static::$app->instance(VexPayClient::class, $fake->client());

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'vexpay';
    }
}
