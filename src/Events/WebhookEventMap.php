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
        'payment.chargeback' => W\PaymentChargeback::class,
        'payment.chargeback_closed' => W\PaymentChargebackClosed::class,
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
        'conversion.created' => W\ConversionCreated::class,
        'conversion.completed' => W\ConversionCompleted::class,
        'conversion.canceled' => W\ConversionCanceled::class,
        'onward_payout.updated' => W\OnwardPayoutUpdated::class,
        'tenant.status_changed' => W\TenantStatusChanged::class,
        'tenant.api_key.created' => W\TenantApiKeyCreated::class,
        'tenant.api_key.rotated' => W\TenantApiKeyRotated::class,
        'tenant.api_key.revoked' => W\TenantApiKeyRevoked::class,
        'tenant.live_status_changed' => W\TenantLiveStatusChanged::class,
        'customer.created' => W\CustomerCreated::class,
        'customer.updated' => W\CustomerUpdated::class,
        'customer.deleted' => W\CustomerDeleted::class,
        'customer.subscription.created' => W\CustomerSubscriptionCreated::class,
        'customer.subscription.updated' => W\CustomerSubscriptionUpdated::class,
        'customer.subscription.deleted' => W\CustomerSubscriptionDeleted::class,
        'customer.subscription.trial_will_end' => W\CustomerSubscriptionTrialWillEnd::class,
        'customer.subscription.reactivated' => W\CustomerSubscriptionReactivated::class,
        'invoice.created' => W\InvoiceCreated::class,
        'invoice.upcoming' => W\InvoiceUpcoming::class,
        'invoice.paid' => W\InvoicePaid::class,
        'invoice.payment_attempt' => W\InvoicePaymentAttempt::class,
        'invoice.payment_failed' => W\InvoicePaymentFailed::class,
        'invoice.overdue' => W\InvoiceOverdue::class,
        'invoice.voided' => W\InvoiceVoided::class,
        'invoice.marked_uncollectible' => W\InvoiceMarkedUncollectible::class,
        'payment_method.attached' => W\PaymentMethodAttached::class,
        'payment_method.updated' => W\PaymentMethodUpdated::class,
        'payment_method.detached' => W\PaymentMethodDetached::class,
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
