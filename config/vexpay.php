<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API credentials
    |--------------------------------------------------------------------------
    |
    | Your secret API key from the VEXPay dashboard. Use the test-mode key while
    | you build. Keep it in .env — never commit it or send it to the browser.
    |
    */

    'api_key' => env('VEXPAY_API_KEY'),

    'base_url' => env('VEXPAY_BASE_URL', 'https://api.pay.vexwallet.co'),

    'timeout' => (int) env('VEXPAY_TIMEOUT', 30),

    'max_network_retries' => (int) env('VEXPAY_MAX_NETWORK_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | The signing secret of the webhook endpoint you registered in VEXPay, and
    | where the package listens. Set `route` to false to mount the controller
    | yourself (keep the VerifyWebhookSignature middleware in front of it).
    |
    */

    'webhook' => [
        'secret' => env('VEXPAY_WEBHOOK_SECRET'),
        'route' => (bool) env('VEXPAY_WEBHOOK_ROUTE', true),
        'path' => env('VEXPAY_WEBHOOK_PATH', 'vexpay/webhook'),
        'tolerance' => (int) env('VEXPAY_WEBHOOK_TOLERANCE', 300),
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Local payments
    |--------------------------------------------------------------------------
    |
    | Checkouts started with the Billable trait are recorded in this table and
    | kept in sync from payment.* webhooks.
    |
    */

    'payments' => [
        'table' => 'vexpay_payments',
        'model' => VexPay\Laravel\Models\Payment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace merchants
    |--------------------------------------------------------------------------
    |
    | Eloquent models using the HasVexPayMerchant trait, so merchant.* and
    | payout.* webhook events can resolve the seller they belong to.
    |
    */

    'merchant_models' => [
        // App\Models\Seller::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedded checkout
    |--------------------------------------------------------------------------
    |
    | Version of @vexpay/js the <x-vexpay-checkout> component loads from the CDN.
    |
    */

    'js_version' => env('VEXPAY_JS_VERSION', '0.2.0'),

];
