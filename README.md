# VEXPay for Laravel

The official Laravel integration for [VEXPay](https://vexwallet.co/vexpay): accept Venezuelan payments (Pago Móvil C2P, cards, débito inmediato, USDT) and pay out to sellers, the Laravel way.

- `VexPay` facade and a container-bound client with the full API ([`vexpay/vexpay-php`](https://github.com/chuventures/vexpay-php))
- Verified webhooks at `/vexpay/webhook`, dispatched as Laravel events
- `Billable` — start a checkout from any Eloquent model and keep a local, webhook-synced payment record
- `HasVexPayMerchant` — onboard sellers as merchants and send idempotent payouts
- `<x-vexpay-checkout>` Blade component for the embedded checkout
- `VexPay::fake()` and signed-webhook helpers for tests that never touch the network

Requires PHP 8.2+ and Laravel 12 or 13 (every Laravel release still receiving security fixes).

## Install

```sh
composer require vexpay/laravel
php artisan vexpay:install
php artisan migrate
```

`vexpay:install` publishes `config/vexpay.php` and the `vexpay_payments` migration, then prints the webhook URL to register. Add your keys to `.env` (VEXPay dashboard — use the test-mode key while you build):

```dotenv
VEXPAY_API_KEY=
VEXPAY_WEBHOOK_SECRET=
```

Then register `https://your-app.example/vexpay/webhook` as a webhook endpoint in the VEXPay dashboard and check delivery with `php artisan vexpay:webhook-test`.

Optional settings: `VEXPAY_BASE_URL`, `VEXPAY_TIMEOUT`, `VEXPAY_MAX_NETWORK_RETRIES`, `VEXPAY_WEBHOOK_PATH`, `VEXPAY_WEBHOOK_TOLERANCE`, `VEXPAY_WEBHOOK_ROUTE`, `VEXPAY_JS_VERSION`.

## Calling the API

The facade (or an injected `VexPay\VexPayClient`) exposes every operation of the PHP SDK, with retries, idempotency keys and typed responses:

```php
use VexPay\Laravel\Facades\VexPay;

$quote = VexPay::quotes()->retrieve(['usdAmount' => 25]);
$payment = VexPay::payments()->retrieve('pay_123');
$banks = VexPay::banks()->list();
```

A missing `VEXPAY_API_KEY` never breaks boot; the first API call throws a `VexPay\Exception\ConfigurationException` naming it.

## Accepting payments with `Billable`

Add the trait to whatever you charge for — an order, an invoice, a user:

```php
use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\Billable;

class Order extends Model
{
    use Billable;
}
```

Return a checkout from a controller and the buyer is redirected (303) to the hosted VEXPay checkout:

```php
use App\Models\Order;

class PayOrderController
{
    public function __invoke(Order $order)
    {
        return $order->checkout($order->total_usd, [
            'description' => "Pedido #{$order->number}",
            'successUrl' => route('orders.thanks', $order),
            'cancelUrl' => route('cart'),
            'metadata' => ['orderNumber' => (string) $order->number],
        ]);
    }
}
```

`checkout()` creates the checkout session and stores a `PENDING` row in `vexpay_payments` linked to the order. Any checkout session field can be passed as an option (`methods`, `allowedOrigins`, `expiresInMinutes`, `reference`); `metadata` also gets `billable_type` / `billable_id`. JSON requests get `{ id, url, clientSecret }` instead of a redirect. Read the records with `$order->vexpayPayments` (newest first); each is a `VexPay\Laravel\Models\Payment` with `status`, `payment_id`, `amount_usd`, `amount_ves`, `bcv_rate`, `method`, `failure_code` and helpers like `isCompleted()`.

### Fulfil from the webhook

VEXPay's `payment.*` webhooks update the local record. Status only moves forward (`PENDING` → `COMPLETED` / `FAILED` / `CANCELED`, `COMPLETED` → `REVERSED`), so retries, duplicates and out-of-order deliveries are harmless, and these events fire **exactly once per transition** — fulfil orders here:

```php
use Illuminate\Support\Facades\Event;
use VexPay\Laravel\Events\Billing\PaymentFailed;
use VexPay\Laravel\Events\Billing\PaymentReversed;
use VexPay\Laravel\Events\Billing\PaymentSucceeded;

Event::listen(function (PaymentSucceeded $event) {
    $order = $event->billable();          // the Order that started the checkout
    $order->markPaid($event->payment->payment_id);
});

Event::listen(function (PaymentFailed $event) {
    logger()->info('payment failed', ['code' => $event->payment->failure_code]);
});

Event::listen(function (PaymentReversed $event) {
    $event->billable()->refundFulfilment();
});
```

`PaymentCanceled` covers checkouts that were superseded or expired. Billing listeners can implement `ShouldQueue`.

### Embedded checkout

To keep the buyer on your page, render the Blade component with the checkout. Only the session's one-time client secret reaches the browser:

```blade
<x-vexpay-checkout :checkout="$checkout" />
```

It mounts [`@vexpay/js`](https://www.npmjs.com/package/@vexpay/js) from the versioned jsDelivr CDN (`VEXPAY_JS_VERSION`). Pass `client-secret="..."` instead of `:checkout` if you only have the secret, `id` to change the mount element, and `checkout-origin` for a non-default checkout host. Add your site's origin to the session's `allowedOrigins`. The mounted instance is available as `window.vexpayCheckouts['vexpay-checkout']` for UI updates:

```blade
<script>
    window.vexpayCheckouts['vexpay-checkout'].on('completed', ({ sessionId }) => {
        window.location.assign('/orders/thanks?session=' + sessionId);
    });
</script>
```

> Browser events are advisory. Never fulfil from them — fulfil from `PaymentSucceeded` (or check the session on your server).

## Webhooks

The package registers `POST /vexpay/webhook` outside the `web` middleware group (no session, no CSRF — the signature is the authentication). Each delivery's `VexPay-Signature` is verified over the raw body; anything missing, invalid, tampered or older than 5 minutes gets a 400 and dispatches nothing.

Every verified delivery dispatches:

1. `VexPay\Laravel\Events\WebhookReceived` — always, including event names newer than your package version.
2. A specific event from `VexPay\Laravel\Events\Webhooks\`, e.g. `PaymentCompleted`, `PaymentFailed`, `PaymentReversed`, `MerchantVerified`, `MerchantBalanceUpdated`, `PayoutCompleted`, `PayoutFailed`, `TenantApiKeyRotated`, `NotificationTest` — one per documented event name.
3. Local payment sync (and the billing events above).
4. `VexPay\Laravel\Events\WebhookHandled`.

Each event carries the verified delivery as `$event->webhook` (`->event`, `->data`, `->timestamp`). Payment events add `payment()` (typed data) and `localPayment()`; merchant and payout events add `owner()`.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use VexPay\Laravel\Events\Webhooks\PayoutFailed;

class NotifySellerOfFailedPayout implements ShouldQueue
{
    public function handle(PayoutFailed $event): void
    {
        $seller = $event->owner();
        $code = $event->webhook->data['failureCode'] ?? null;
        // ...
    }
}
```

These per-delivery events fire again when VEXPay retries or resends a delivery (the `VexPay-Event-Id` header is per attempt), so make listeners idempotent — or use the billing events, which already are.

To mount the endpoint yourself, set `VEXPAY_WEBHOOK_ROUTE=false` and keep the middleware in front of the controller:

```php
use Illuminate\Support\Facades\Route;
use VexPay\Laravel\Http\Controllers\WebhookController;
use VexPay\Laravel\Http\Middleware\VerifyWebhookSignature;

Route::post('/hooks/vexpay', WebhookController::class)->middleware(VerifyWebhookSignature::class);
```

## Marketplace sellers with `HasVexPayMerchant`

Give your seller model a `vexpay_merchant_id` column (`php artisan vexpay:install --merchant-table=sellers` publishes the migration) and the trait, and list it in `config/vexpay.php` → `merchant_models` so webhook events can find it:

```php
use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\HasVexPayMerchant;

class Seller extends Model
{
    use HasVexPayMerchant;
}
```

```php
$seller->createAsVexPayMerchant([
    'name' => 'María Pérez',
    'identification' => 'V12345678',
    'contactEmail' => 'maria@example.com',
    'contactPhone' => '04141234567',
]);

$method = $seller->addVexPayPayoutMethod(['bankCode' => '0102', 'phone' => '04141234567']);
$seller->startVexPayPayoutMethodVerification($method->payoutMethodId);
// later, with what the seller received:
$seller->confirmVexPayPayoutMethodVerification($method->payoutMethodId, ['amount' => '1.23']);

$seller->vexpayMerchantStatus();   // e.g. "verified"
$seller->vexpayBalance();

$seller->payout(1523.40, 'Venta #991', 'order-991');
```

The merchant's external reference is `<morph alias>:<key>` (register a [morph map](https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types) to keep it short and rename-proof), so retrying onboarding adopts the merchant that already exists instead of failing. The third argument of `payout()` is the payout's idempotency key: calling it again with the same reference never pays twice. Omit it and a random one is generated, which only protects the retries of that one call. `MerchantVerified`, `MerchantBalanceUpdated`, `PayoutCompleted`, `PayoutFailed` and the other merchant/payout events expose the seller via `owner()`.

## Testing

`VexPay::fake()` swaps the client for an in-memory one: nothing leaves the process, every operation answers with a valid response (echoing what you sent), and calls can be asserted. Stub responses by `operationId`. Signed webhooks come from the `InteractsWithVexPay` trait:

```php
use App\Models\Order;
use Tests\TestCase;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Testing\InteractsWithVexPay;
use VexPay\Testing\FakeHttpClient;

class CheckoutTest extends TestCase
{
    use InteractsWithVexPay;

    public function test_paid_orders_are_fulfilled(): void
    {
        config(['vexpay.webhook.secret' => 'whsec_test']);
        VexPay::fake(['CheckoutSessions_create' => ['url' => 'https://pay.vexwallet.co/c/cs_1']]);
        $order = Order::factory()->create(['total_usd' => 25]);

        $this->post(route('orders.pay', $order))->assertRedirect('https://pay.vexwallet.co/c/cs_1');
        VexPay::assertCheckoutCreated(25.00);

        $this->postVexPayPaymentWebhook($order->vexpayPayments()->first(), 'payment.completed')->assertNoContent();
        $this->assertTrue($order->fresh()->is_paid);
    }

    public function test_payout_conflicts_are_reported(): void
    {
        VexPay::fake(['Payouts_create' => FakeHttpClient::error(409, 'external_ref_conflict')]);
        // ...
        VexPay::assertPayoutCreated(fn (array $params) => $params['externalRef'] === 'order-991');
    }
}
```

Also available: `VexPay::assertSent($operationId, $callback)`, `assertNotSent`, `assertNothingSent`, `assertMerchantCreated`, `postVexPayWebhook($event, $data)` for any event name, and `VexPay\Laravel\Testing\SignedWebhook` to build signed payloads yourself.

## Artisan

| Command | |
|---|---|
| `vexpay:install [--merchant-table=]` | Publish config and migrations, print setup steps |
| `vexpay:webhook-test` | Ask VEXPay to send `notification.test` to your registered endpoints |

## License

MIT
