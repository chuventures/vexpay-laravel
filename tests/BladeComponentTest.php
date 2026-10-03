<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Tests\Fixtures\Order;

final class BladeComponentTest extends TestCase
{
    public function testRendersTheEmbedWithOnlyTheClientSecret(): void
    {
        VexPay::fake(['CheckoutSessions_create' => ['clientSecret' => 'cs_secret_abc']]);
        $checkout = Order::create(['number' => '1'])->checkout(25);

        $html = Blade::render('<x-vexpay-checkout :checkout="$checkout" class="my-checkout" />', ['checkout' => $checkout]);

        self::assertStringContainsString('cs_secret_abc', $html);
        self::assertStringContainsString('https://cdn.jsdelivr.net/npm/@vexpay/js@0.2.0/dist/vexpay.global.js', $html);
        self::assertStringContainsString('<div id="vexpay-checkout" class="my-checkout"></div>', $html);
        self::assertStringContainsString('mount("#vexpay-checkout")', $html);
        self::assertStringNotContainsString(self::API_KEY, $html);
        self::assertStringNotContainsString(self::WEBHOOK_SECRET, $html);
    }

    public function testAcceptsAClientSecretAndVersionDirectly(): void
    {
        $html = Blade::render('<x-vexpay-checkout client-secret="cs_secret_xyz" id="pay-here" version="0.3.0" />');

        self::assertStringContainsString('cs_secret_xyz', $html);
        self::assertStringContainsString('@vexpay/js@0.3.0/', $html);
        self::assertStringContainsString('mount("#pay-here")', $html);
    }

    public function testSecretIsJsonEscapedInsideTheScript(): void
    {
        $html = Blade::render('<x-vexpay-checkout client-secret="</script><script>alert(1)</script>" />');

        self::assertStringNotContainsString('<script>alert(1)', $html);
    }

    public function testRequiresASecret(): void
    {
        $this->expectException(\Throwable::class);
        Blade::render('<x-vexpay-checkout />');
    }
}
