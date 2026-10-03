<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use VexPay\Laravel\Facades\VexPay;
use VexPay\Testing\FakeHttpClient;

final class WebhookTestCommandTest extends TestCase
{
    public function testReportsSuccess(): void
    {
        VexPay::fake(['Notifications_sendTest' => ['sent' => true, 'event' => 'notification.test']]);

        $this->artisan('vexpay:webhook-test')
            ->expectsOutputToContain('notification.test')
            ->assertSuccessful();

        VexPay::assertSent('Notifications_sendTest');
    }

    public function testPrintsTheTypedApiError(): void
    {
        VexPay::fake(['Notifications_sendTest' => FakeHttpClient::error(401, 'invalid_api_key', 'Invalid API key')]);

        $this->artisan('vexpay:webhook-test')
            ->expectsOutputToContain('invalid_api_key')
            ->assertFailed();
    }

    public function testWarnsWhenNothingWasSent(): void
    {
        VexPay::fake(['Notifications_sendTest' => ['sent' => false]]);

        $this->artisan('vexpay:webhook-test')->assertFailed();
    }
}
