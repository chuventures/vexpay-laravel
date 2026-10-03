<?php

declare(strict_types=1);

namespace VexPay\Laravel\Testing;

use Illuminate\Testing\TestResponse;
use VexPay\Laravel\Models\Payment;

/**
 * Use in Laravel feature tests to post signed VEXPay webhooks to your webhook route.
 *
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait InteractsWithVexPay
{
    /**
     * @param array<string, mixed> $data
     */
    protected function postVexPayWebhook(string $event, array $data = []): TestResponse
    {
        return $this->sendVexPayWebhook(SignedWebhook::make($event, $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function postVexPayPaymentWebhook(Payment $payment, string $event = 'payment.completed', array $data = []): TestResponse
    {
        return $this->sendVexPayWebhook(SignedWebhook::forPayment($payment, $event, $data));
    }

    protected function sendVexPayWebhook(SignedWebhook $webhook): TestResponse
    {
        return $this->call(
            'POST',
            '/' . ltrim((string) config('vexpay.webhook.path', 'vexpay/webhook'), '/'),
            server: $this->transformHeadersToServerVars($webhook->headers),
            content: $webhook->body,
        );
    }
}
