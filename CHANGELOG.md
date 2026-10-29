# Changelog

## 0.1.11

- Allow `vexpay/vexpay-php` 0.12: COP channels `pse`, `bancolombia_transfer` and `bancolombia_qr`, and `VexPay::cop()->channels()` for the channel catalog (PSE banks, terms links).
- With `vexpay/vexpay-php` 0.12: disbursements (`VexPay::merchants()->transfer()`, `listTransactions()`, `retrieveStatement()`, `retrieveStatementCsv()`) and balance top-ups (`VexPay::balance()->topups`). `PayoutCompleted` / `PayoutFailed` add `feePaidByTenant()` and `feeVes()`.

## 0.1.10

- Allow `vexpay/vexpay-php` 0.11: billing resources `VexPay::customers()`, `prices()`, `subscriptions()`, `invoices()`, `setupSessions()` and `paymentMethods()`. The `Billable` trait (one-time checkouts) is unchanged.

## 0.1.9

- Allow `vexpay/vexpay-php` 0.10: split a checkout session with a merchant. `$user->checkout($amount, ['merchantId' => …, 'applicationFeePercent' => 3, 'methods' => ['pago_movil']])`.

## 0.1.8

- Allow `vexpay/vexpay-php` 0.9: held balances (`heldVes`/`held`/`heldCop`, `nextReleaseAt`, `balanceHoldHours`) and the onward payout `waiting_release` status.

## 0.1.7

- Allow `vexpay/vexpay-php` 0.8: `onwardPayout` on Pago Móvil and C2P pay-ins, and `rate`/`rateSource` on quotes and receipts. New `OnwardPayoutUpdated` event for the `onward_payout.updated` webhook.

## 0.1.6

- Allow `vexpay/vexpay-php` 0.7: `VexPay::payments()->methods()` and C2P `vesAmount`.

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
