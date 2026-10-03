# Changelog

## 0.1.0

- First release: `VexPay` facade and container-bound client, verified webhook route dispatching Laravel events, `Billable` checkouts with a webhook-synced `vexpay_payments` table and exactly-once billing events, `HasVexPayMerchant` onboarding and idempotent payouts, `<x-vexpay-checkout>` Blade component, `VexPay::fake()` with signed-webhook test helpers, and the `vexpay:install` / `vexpay:webhook-test` commands.
