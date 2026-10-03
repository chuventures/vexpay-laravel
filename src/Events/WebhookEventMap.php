<?php

declare(strict_types=1);

namespace VexPay\Laravel\Events;

use VexPay\Laravel\Events\Webhooks as W;

/**
 * Webhook event name → specific Laravel event class.
 */
final class WebhookEventMap
{
    /** @var array<string, class-string<VexPayWebhookEvent>> */
    public const EVENTS = [
        'payment.pending' => W\PaymentPending::class,
        'payment.completed' => W\PaymentCompleted::class,
        'payment.failed' => W\PaymentFailed::class,
        'payment.canceled' => W\PaymentCanceled::class,
        'payment.reversed' => W\PaymentReversed::class,
        'merchant.verified' => W\MerchantVerified::class,
        'merchant.rejected' => W\MerchantRejected::class,
        'merchant.deactivated' => W\MerchantDeactivated::class,
        'merchant.reactivated' => W\MerchantReactivated::class,
        'merchant.balance.updated' => W\MerchantBalanceUpdated::class,
        'merchant.created' => W\MerchantCreated::class,
        'merchant.activated' => W\MerchantActivated::class,
        'merchant.updated' => W\MerchantUpdated::class,
        'merchant.kyb_required' => W\MerchantKybRequired::class,
        'merchant.restricted' => W\MerchantRestricted::class,
        'merchant.capability.updated' => W\MerchantCapabilityUpdated::class,
        'merchant.wallet_credit' => W\MerchantWalletCredit::class,
        'payout.completed' => W\PayoutCompleted::class,
        'payout.failed' => W\PayoutFailed::class,
        'tenant.status_changed' => W\TenantStatusChanged::class,
        'tenant.api_key.created' => W\TenantApiKeyCreated::class,
        'tenant.api_key.rotated' => W\TenantApiKeyRotated::class,
        'tenant.api_key.revoked' => W\TenantApiKeyRevoked::class,
        'tenant.live_status_changed' => W\TenantLiveStatusChanged::class,
        'notification.test' => W\NotificationTest::class,
    ];

    /**
     * @return class-string<VexPayWebhookEvent>|null
     */
    public static function eventFor(string $name): ?string
    {
        return self::EVENTS[$name] ?? null;
    }
}
