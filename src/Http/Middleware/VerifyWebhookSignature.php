<?php

declare(strict_types=1);

namespace VexPay\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VexPay\Exception\ConfigurationException;
use VexPay\Exception\SignatureVerificationException;
use VexPay\Webhook;

/**
 * Verifies `VexPay-Signature` over the exact raw body and hands the typed event to the
 * controller. Rejected deliveries never reach application code.
 */
final class VerifyWebhookSignature
{
    public const EVENT_ATTRIBUTE = 'vexpay.webhook';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('vexpay.webhook.secret');
        if (blank($secret)) {
            report(new ConfigurationException(
                'VEXPay webhook received but no signing secret is configured. Set VEXPAY_WEBHOOK_SECRET in your .env.',
            ));

            return response()->json(['error' => 'webhook_secret_not_configured'], 500);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header(Webhook::SIGNATURE_HEADER),
                $secret,
                (int) config('vexpay.webhook.tolerance', Webhook::DEFAULT_TOLERANCE),
            );
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'invalid_signature', 'message' => $e->getMessage()], 400);
        }

        $request->attributes->set(self::EVENT_ATTRIBUTE, $event);

        return $next($request);
    }
}
