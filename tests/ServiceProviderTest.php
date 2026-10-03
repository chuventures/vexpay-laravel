<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Route;
use VexPay\Exception\ConfigurationException;
use VexPay\Generated\Models\PaymentReceiptDto;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\VexPayServiceProvider;
use VexPay\Testing\FakeHttpClient;
use VexPay\VexPayClient;

final class ServiceProviderTest extends TestCase
{
    public function testProviderBootsAndBindsOneClient(): void
    {
        self::assertInstanceOf(VexPayClient::class, $this->app->make(VexPayClient::class));
        self::assertSame($this->app->make(VexPayClient::class), $this->app->make(VexPayClient::class));
        self::assertSame($this->app->make(VexPayClient::class), VexPay::getFacadeRoot());
    }

    public function testFacadeCallsUseConfiguredKeyAndBaseUrl(): void
    {
        $http = new FakeHttpClient();
        $this->app->instance(VexPayServiceProvider::HTTP_CLIENT, $http);

        $payment = VexPay::payments()->retrieve('pay_123');

        self::assertInstanceOf(PaymentReceiptDto::class, $payment);
        $request = $http->recorded()[0];
        self::assertSame('Payments_getPayment', $request->operationId);
        self::assertSame(self::API_KEY, $request->header('x-api-key'));
        self::assertSame('/v1/payments/pay_123', $request->path);
    }

    public function testMissingApiKeyFailsAtCallTimeNamingTheEnvVar(): void
    {
        config(['vexpay.api_key' => null]);
        $this->app->forgetInstance(VexPayClient::class);
        $this->app->forgetInstance('vexpay');
        VexPay::clearResolvedInstances();

        try {
            VexPay::payments();
            self::fail('expected a configuration error');
        } catch (ConfigurationException $e) {
            self::assertStringContainsString('VEXPAY_API_KEY', $e->getMessage());
        }
    }

    public function testWebhookRouteIsRegisteredOutsideTheWebGroup(): void
    {
        $route = Route::getRoutes()->getByName('vexpay.webhook');

        self::assertNotNull($route);
        self::assertSame('vexpay/webhook', $route->uri());
        self::assertSame(['POST'], $route->methods());
        self::assertNotContains('web', $route->gatherMiddleware());
    }
}
