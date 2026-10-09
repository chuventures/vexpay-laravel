# Changelog

## 0.1.5

- Allow `vexpay/vexpay-php` 0.6, whose conversions accept COP and add auto-convert settings. New `ConversionCreated` event for the `conversion.created` webhook.

## 0.1.4

- Allow `vexpay/vexpay-php` 0.5, which adds `cop` (Colombian pesos: Bre-B, Nequi, Daviplata). `VexPay::cop()` is documented on the facade, and a Billable checkout paid in COP syncs with `method` `COP`.

## 0.1.3

- Allow `vexpay/vexpay-php` 0.4, whose checkout sessions accept `cop` (Colombian pesos: Bre-B, Nequi, Daviplata).

## 0.1.2

- Allow `vexpay/vexpay-php` 0.3, which adds `balance->transactions->list()` (every movement in your VES balance, for automatic reconciliation). The new `payment.chargeback` / `payment.chargeback_closed` webhooks dispatch `PaymentChargeback` / `PaymentChargebackClosed` events.

## 0.1.1

- Allow `vexpay/vexpay-php` 0.2, which adds `conversions` (convert available VES to USDT). `VexPay::conversions()` is now documented on the facade, and the new `conversion.completed` / `conversion.canceled` webhooks dispatch `ConversionCompleted` / `ConversionCanceled` events.

## 0.1.0

- First release: `VexPay` facade and container-bound client, verified webhook route dispatching Laravel events, `Billable` checkouts with a webhook-synced `vexpay_payments` table and exactly-once billing events, `HasVexPayMerchant` onboarding and idempotent payouts, `<x-vexpay-checkout>` Blade component, `VexPay::fake()` with signed-webhook test helpers, and the `vexpay:install` / `vexpay:webhook-test` commands.
