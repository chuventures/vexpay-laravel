<?php

declare(strict_types=1);

namespace VexPay\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use VexPay\Laravel\Events\WebhookEventMap;
use VexPay\Laravel\Events\WebhookHandled;
use VexPay\Laravel\Events\WebhookReceived;
use VexPay\Laravel\Http\Middleware\VerifyWebhookSignature;
use VexPay\Laravel\Sync\PaymentSynchronizer;
use VexPay\Webhook\WebhookEvent;

/**
 * Dispatches events for a verified delivery and syncs local payments before answering, so
 * VEXPay only sees a 2xx once local state is persisted (a failure here makes VEXPay retry).
 */
final class WebhookController
{
    public function __invoke(Request $request, PaymentSynchronizer $payments): Response
    {
        $event = $request->attributes->get(VerifyWebhookSignature::EVENT_ATTRIBUTE);
        if (!$event instanceof WebhookEvent) {
            throw new \LogicException(sprintf(
                'The VEXPay webhook controller must run behind the %s middleware.',
                VerifyWebhookSignature::class,
            ));
        }

        event(new WebhookReceived($event));
        $specific = WebhookEventMap::eventFor($event->event);
        if ($specific !== null) {
            event(new $specific($event));
        }

        if ($event->isPaymentEvent()) {
            $payments->sync($event);
        }

        event(new WebhookHandled($event));

        return response()->noContent();
    }
}
