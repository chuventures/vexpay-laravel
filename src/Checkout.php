<?php

declare(strict_types=1);

namespace VexPay\Laravel;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VexPay\Generated\Models\CheckoutSessionResponseDto;
use VexPay\Laravel\Models\Payment;

/**
 * A started checkout. Return it from a controller to redirect the buyer to the hosted page
 * (JSON requests get `{ id, url, clientSecret }` for the embed), or pass it to
 * `<x-vexpay-checkout :checkout="$checkout" />`.
 */
final class Checkout implements Responsable
{
    public function __construct(
        public readonly CheckoutSessionResponseDto $session,
        public readonly Payment $payment,
    ) {
    }

    public function id(): string
    {
        return $this->session->id;
    }

    public function url(): string
    {
        return $this->session->url;
    }

    /**
     * One-time secret for the embedded checkout. Send it to the browser; never log or store it.
     */
    public function clientSecret(): ?string
    {
        return $this->session->clientSecret;
    }

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse($this->url(), 303);
    }

    /**
     * @param Request $request
     */
    public function toResponse($request): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'id' => $this->id(),
                'url' => $this->url(),
                'clientSecret' => $this->clientSecret(),
            ]);
        }

        return $this->redirect();
    }
}
