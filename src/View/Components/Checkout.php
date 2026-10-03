<?php

declare(strict_types=1);

namespace VexPay\Laravel\View\Components;

use Illuminate\View\Component;
use VexPay\Generated\Models\CheckoutSessionResponseDto;
use VexPay\Laravel\Checkout as StartedCheckout;

/**
 * `<x-vexpay-checkout :checkout="$checkout" />` — mounts the embedded @vexpay/js checkout.
 * Only the session's one-time client secret reaches the page. Browser events are advisory:
 * fulfil from the PaymentSucceeded event (webhook), never from the page.
 */
final class Checkout extends Component
{
    public string $clientSecret;

    public string $scriptUrl;

    public function __construct(
        StartedCheckout|CheckoutSessionResponseDto|null $checkout = null,
        ?string $clientSecret = null,
        public string $id = 'vexpay-checkout',
        public ?string $checkoutOrigin = null,
        ?string $version = null,
    ) {
        $session = $checkout instanceof StartedCheckout ? $checkout->session : $checkout;
        $secret = $clientSecret ?? $session?->clientSecret;
        if ($secret === null || $secret === '') {
            throw new \InvalidArgumentException('<x-vexpay-checkout> needs a checkout session with a client secret (pass :checkout or client-secret).');
        }
        $this->clientSecret = $secret;
        $this->scriptUrl = sprintf(
            'https://cdn.jsdelivr.net/npm/@vexpay/js@%s/dist/vexpay.global.js',
            rawurlencode($version ?? (string) config('vexpay.js_version', '0.2.0')),
        );
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return array_filter([
            'clientSecret' => $this->clientSecret,
            'checkoutOrigin' => $this->checkoutOrigin,
        ], static fn ($value) => $value !== null);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('vexpay::components.checkout');
    }
}
