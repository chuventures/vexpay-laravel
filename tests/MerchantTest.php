<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use VexPay\Laravel\Events\Webhooks\MerchantVerified;
use VexPay\Laravel\Events\Webhooks\PayoutCompleted;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Tests\Fixtures\Seller;
use VexPay\Testing\FakeHttpClient;
use VexPay\Testing\RecordedRequest;

final class MerchantTest extends TestCase
{
    private const PARAMS = [
        'name' => 'María Pérez',
        'identification' => 'V12345678',
        'contactEmail' => 'maria@example.com',
        'contactPhone' => '04141234567',
    ];

    public function testOnboardingUsesAStableRefAndStoresTheMerchantId(): void
    {
        VexPay::fake(['Merchants_create' => ['merchantId' => 'mrc_1']]);
        $seller = Seller::create(['name' => 'María']);

        $merchant = $seller->createAsVexPayMerchant(self::PARAMS);

        self::assertSame('mrc_1', $merchant->merchantId);
        self::assertSame('mrc_1', $seller->fresh()->vexpay_merchant_id);
        VexPay::assertMerchantCreated(static fn (array $body) => $body['externalRef'] === Seller::class . ':' . $seller->id
            && $body['name'] === 'María Pérez');
    }

    public function testAnExistingMerchantForTheRefIsAdopted(): void
    {
        VexPay::fake([
            'Merchants_create' => FakeHttpClient::error(409, 'external_ref_conflict'),
            'Merchants_listOrGetByRef' => ['merchantId' => 'mrc_existing'],
        ]);
        $seller = Seller::create(['name' => 'María']);

        $seller->createAsVexPayMerchant(self::PARAMS);

        self::assertSame('mrc_existing', $seller->fresh()->vexpay_merchant_id);
    }

    public function testCannotOnboardTwice(): void
    {
        VexPay::fake();
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);

        $this->expectException(\LogicException::class);
        $seller->createAsVexPayMerchant(self::PARAMS);
    }

    public function testStatusBalanceAndPayoutMethods(): void
    {
        $fake = VexPay::fake(['Merchants_getById' => ['status' => 'verified']]);
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);

        self::assertSame('verified', $seller->vexpayMerchantStatus());
        $seller->vexpayBalance();
        $seller->addVexPayPayoutMethod(['bankCode' => '0102', 'phone' => '04141234567']);
        $seller->startVexPayPayoutMethodVerification('pm_1');
        $seller->confirmVexPayPayoutMethodVerification('pm_1', ['amount' => '1.23']);

        self::assertSame(
            ['Merchants_getById', 'Merchants_getBalance', 'Merchants_addPayoutMethod', 'Merchants_startVerifyMethod', 'Merchants_confirmVerifyMethod'],
            array_map(static fn (RecordedRequest $r) => $r->operationId, $fake->recorded()),
        );
        self::assertSame('/v1/merchants/mrc_1/payout-methods', $fake->recorded()[2]->path);
    }

    public function testRepeatedPayoutsReuseTheReference(): void
    {
        $fake = VexPay::fake();
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);

        $seller->payout(1523.4, 'Venta 991', 'order-991');
        $seller->payout(1523.4, 'Venta 991', 'order-991');

        $sent = $fake->recorded('Payouts_create');
        self::assertCount(2, $sent);
        foreach ($sent as $request) {
            self::assertSame(['merchantId' => 'mrc_1', 'monto' => '1523.40', 'concepto' => 'Venta 991', 'externalRef' => 'order-991'], $request->body);
            self::assertSame('payout-order-991', $request->header('Idempotency-Key'));
        }
    }

    public function testPayoutWithoutReferenceGeneratesOne(): void
    {
        VexPay::fake();
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);

        $seller->payout('10.00', 'Venta');

        VexPay::assertPayoutCreated(static fn (array $body) => str_starts_with($body['externalRef'], 'payout_') && $body['monto'] === '10.00');
    }

    public function testPayoutBeforeOnboardingFails(): void
    {
        VexPay::fake();
        $this->expectException(\LogicException::class);
        Seller::create(['name' => 'María'])->payout('10.00', 'Venta');
    }

    public function testMerchantEventsResolveTheOwningModel(): void
    {
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);
        Event::fake([MerchantVerified::class]);

        $this->postVexPayWebhook('merchant.verified', [
            'merchantId' => 'mrc_1',
            'externalRef' => $seller->vexpayMerchantExternalRef(),
            'payoutMethodId' => 'pm_1',
        ])->assertNoContent();

        Event::assertDispatched(MerchantVerified::class, static fn (MerchantVerified $e) => $e->owner()?->is($seller) === true);
    }

    public function testPayoutEventsResolveTheSellerByMerchantId(): void
    {
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);
        Event::fake([PayoutCompleted::class]);

        $this->postVexPayWebhook('payout.completed', ['payoutId' => 'po_1', 'merchantId' => 'mrc_1', 'externalRef' => 'order-991'])
            ->assertNoContent();

        Event::assertDispatched(PayoutCompleted::class, static fn (PayoutCompleted $e) => $e->owner()?->is($seller) === true);
    }

    public function testRefOfADifferentMerchantDoesNotResolve(): void
    {
        $seller = Seller::create(['name' => 'María', 'vexpay_merchant_id' => 'mrc_1']);
        Event::fake([MerchantVerified::class]);

        $this->postVexPayWebhook('merchant.verified', ['merchantId' => 'mrc_2', 'externalRef' => $seller->vexpayMerchantExternalRef()]);

        Event::assertDispatched(MerchantVerified::class, static fn (MerchantVerified $e) => $e->owner() === null);
    }
}
