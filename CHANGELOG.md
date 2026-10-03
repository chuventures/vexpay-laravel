# Changelog

## 0.1.1

- Allow `vexpay/vexpay-php` 0.2, which adds `conversions` (convert available VES to USDT). `VexPay::conversions()` is now documented on the facade, and the new `conversion.completed` / `conversion.canceled` webhooks dispatch `ConversionCompleted` / `ConversionCanceled` events.

## 0.1.0

- First release: `VexPay` facade and container-bound client, verified webhook route dispatching Laravel events, `Billable` checkouts with a webhook-synced `vexpay_payments` table and exactly-once billing events, `HasVexPayMerchant` onboarding and idempotent payouts, `<x-vexpay-checkout>` Blade component, `VexPay::fake()` with signed-webhook test helpers, and the `vexpay:install` / `vexpay:webhook-test` commands.
