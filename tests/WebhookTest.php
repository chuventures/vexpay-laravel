<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use VexPay\Laravel\Events\VexPayWebhookEvent;
use VexPay\Laravel\Events\WebhookEventMap;
use VexPay\Laravel\Events\WebhookHandled;
use VexPay\Laravel\Events\WebhookReceived;
use VexPay\Laravel\Events\Webhooks\NotificationTest;
use VexPay\Laravel\Events\Webhooks\PaymentCompleted;
use VexPay\Laravel\Events\Webhooks\PayoutFailed;
use VexPay\Laravel\Testing\SignedWebhook;
use VexPay\Laravel\Tests\Fixtures\QueuedPayoutListener;
use VexPay\Webhook;

final class WebhookTest extends TestCase
{
    public function testSignedDeliveryDispatchesGenericSpecificAndHandledEvents(): void
    {
        Event::fake([WebhookReceived::class, PaymentCompleted::class, WebhookHandled::class]);

        $this->postVexPayWebhook('payment.completed', ['paymentId' => 'pay_1', 'status' => 'COMPLETED'])
            ->assertNoContent();

        Event::assertDispatched(WebhookReceived::class, static fn (WebhookReceived $e) => $e->webhook->event === 'payment.completed');
        Event::assertDispatched(PaymentCompleted::class, static fn (PaymentCompleted $e) => $e->payment()->paymentId === 'pay_1');
        Event::assertDispatched(WebhookHandled::class);
    }

    public function testInvalidSignatureIsRejectedWithoutEvents(): void
    {
        Event::fake();
        $webhook = SignedWebhook::make('payment.completed', ['paymentId' => 'pay_1'], 'whsec_someone_else');

        $this->sendVexPayWebhook($webhook)->assertStatus(400)->assertJson(['error' => 'invalid_signature']);

        $this->assertNoVexPayEvents();
    }

    public function testMissingSignatureIsRejected(): void
    {
        Event::fake();
        $this->call('POST', '/vexpay/webhook', content: '{"event":"payment.completed","data":{},"timestamp":"x"}')
            ->assertStatus(400);

        $this->assertNoVexPayEvents();
    }

    public function testStaleDeliveryIsRejected(): void
    {
        Event::fake();
        $webhook = SignedWebhook::make('payment.completed', ['paymentId' => 'pay_1'], timestamp: time() - 3600);

        $this->sendVexPayWebhook($webhook)->assertStatus(400);

        $this->assertNoVexPayEvents();
    }

    public function testTamperedBodyIsRejected(): void
    {
        Event::fake();
        $webhook = SignedWebhook::make('payment.completed', ['paymentId' => 'pay_1', 'usdAmount' => 1]);

        $this->call(
            'POST',
            '/vexpay/webhook',
            server: $this->transformHeadersToServerVars($webhook->headers),
            content: str_replace('"usdAmount":1', '"usdAmount":1000', $webhook->body),
        )->assertStatus(400);

        $this->assertNoVexPayEvents();
    }

    public function testMissingSecretAnswers500SoVexPayRetries(): void
    {
        $webhook = SignedWebhook::make('notification.test');
        config(['vexpay.webhook.secret' => null]);
        Event::fake();

        $this->sendVexPayWebhook($webhook)->assertStatus(500);

        $this->assertNoVexPayEvents();
    }

    public function testUnknownEventNameDispatchesOnlyTheGenericEvent(): void
    {
        Event::fake();

        $this->postVexPayWebhook('invoice.finalized', ['id' => 'inv_1'])->assertNoContent();

        Event::assertDispatched(WebhookReceived::class);
        Event::assertDispatched(WebhookHandled::class);
        Event::assertDispatchedTimes(WebhookReceived::class, 1);
        foreach (WebhookEventMap::EVENTS as $class) {
            Event::assertNotDispatched($class);
        }
    }

    public function testQueuedListenersRunOnTheQueue(): void
    {
        Queue::fake();
        Event::listen(PayoutFailed::class, QueuedPayoutListener::class);

        $this->postVexPayWebhook('payout.failed', ['payoutId' => 'po_1', 'failureCode' => 'merchant_inactive'])
            ->assertNoContent();

        Queue::assertPushed(CallQueuedListener::class, static fn (CallQueuedListener $job) => $job->class === QueuedPayoutListener::class);
    }

    public function testNotificationTestEvent(): void
    {
        Event::fake([NotificationTest::class]);

        $this->postVexPayWebhook('notification.test', ['tenantId' => 't_1'])->assertNoContent();

        Event::assertDispatched(NotificationTest::class);
    }

    private function assertNoVexPayEvents(): void
    {
        $classes = [WebhookReceived::class, WebhookHandled::class, ...array_values(WebhookEventMap::EVENTS)];
        foreach ($classes as $class) {
            Event::assertNotDispatched($class);
        }
    }

    public function testEveryDocumentedEventNameHasAnEventClass(): void
    {
        self::assertSame(Webhook::EVENT_NAMES, array_keys(WebhookEventMap::EVENTS));
        foreach (WebhookEventMap::EVENTS as $class) {
            self::assertTrue(is_subclass_of($class, VexPayWebhookEvent::class), $class);
        }
    }
}
