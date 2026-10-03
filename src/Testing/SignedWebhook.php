<?php

declare(strict_types=1);

namespace VexPay\Laravel\Testing;

use VexPay\Laravel\Models\Payment;
use VexPay\Webhook;

/**
 * A webhook delivery signed exactly like VEXPay signs it, for exercising your webhook
 * handling end to end in tests.
 */
final class SignedWebhook
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        public readonly string $body,
        public readonly array $headers,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function make(string $event, array $data = [], ?string $secret = null, ?int $timestamp = null): self
    {
        $secret ??= config('vexpay.webhook.secret');
        if (!is_string($secret) || $secret === '') {
            throw new \LogicException('Set vexpay.webhook.secret (VEXPAY_WEBHOOK_SECRET) in your test environment to sign test webhooks.');
        }
        $body = json_encode([
            'event' => $event,
            'data' => (object) $data,
            'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z', $timestamp ?? time()),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($body, [
            Webhook::SIGNATURE_HEADER => Webhook::generateTestHeader($body, $secret, $timestamp),
            Webhook::EVENT_ID_HEADER => 'whd_test_' . bin2hex(random_bytes(8)),
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * A payment.* delivery for a checkout started with the Billable trait.
     *
     * @param array<string, mixed> $data overrides, e.g. ['failureCode' => 'insufficient_funds']
     */
    public static function forPayment(Payment $payment, string $event = 'payment.completed', array $data = []): self
    {
        $status = strtoupper(substr($event, strlen('payment.')));

        return self::make($event, array_replace([
            'paymentId' => $payment->payment_id ?? 'pay_test_' . $payment->getKey(),
            'status' => $status,
            'usdAmount' => (float) $payment->amount_usd,
            'livemode' => (bool) $payment->livemode,
            'checkoutSession' => [
                'id' => $payment->checkout_session_id,
                'reference' => $payment->reference,
                'metadata' => (object) ($payment->metadata ?? []),
            ],
        ], $data));
    }
}
