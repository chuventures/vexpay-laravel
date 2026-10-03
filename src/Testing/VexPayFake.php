<?php

declare(strict_types=1);

namespace VexPay\Laravel\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use VexPay\Testing\FakeHttpClient;
use VexPay\Testing\RecordedRequest;
use VexPay\VexPayClient;

/**
 * Installed by `VexPay::fake()`. Wraps a real VexPayClient on an in-memory transport, so the
 * traits and your code run unchanged while nothing leaves the process. Resource calls on the
 * facade are forwarded to that client.
 *
 * @mixin VexPayClient
 */
final class VexPayFake
{
    private FakeHttpClient $http;

    private VexPayClient $client;

    /**
     * @param array<string, mixed> $stubs responses keyed by operationId
     */
    public function __construct(array $stubs = [])
    {
        $this->http = new FakeHttpClient($stubs);
        $this->client = new VexPayClient([
            'api_key' => 'vexpay_fake_key',
            'http_client' => $this->http,
            'max_network_retries' => 0,
        ]);
    }

    public function client(): VexPayClient
    {
        return $this->client;
    }

    /**
     * Answer an operation with an array merged over the generated body, a
     * `callable(RecordedRequest)`, or a response such as `FakeHttpClient::error(409, 'external_ref_conflict')`.
     */
    public function stub(string $operationId, mixed $response): self
    {
        $this->http->stub($operationId, $response);

        return $this;
    }

    /**
     * @return list<RecordedRequest>
     */
    public function recorded(?string $operationId = null): array
    {
        return $this->http->recorded($operationId);
    }

    /**
     * @param callable(array<string, mixed>, RecordedRequest): bool|null $callback receives the request body
     */
    public function assertSent(string $operationId, ?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty(
            $this->matching($operationId, $callback),
            sprintf('Expected VEXPay %s to be called%s, but it was not.', $operationId, $callback ? ' with matching parameters' : ''),
        );
    }

    /**
     * @param callable(array<string, mixed>, RecordedRequest): bool|null $callback
     */
    public function assertNotSent(string $operationId, ?callable $callback = null): void
    {
        PHPUnit::assertEmpty(
            $this->matching($operationId, $callback),
            sprintf('Unexpected VEXPay %s call.', $operationId),
        );
    }

    public function assertNothingSent(): void
    {
        $sent = array_map(static fn (RecordedRequest $r) => $r->operationId, $this->http->recorded());
        PHPUnit::assertSame([], $sent, 'Unexpected VEXPay calls: ' . implode(', ', $sent));
    }

    /**
     * @param float|int|callable(array<string, mixed>, RecordedRequest): bool|null $amountOrCallback USD amount, or a callback on the request body
     */
    public function assertCheckoutCreated(float|int|callable|null $amountOrCallback = null): void
    {
        if (is_int($amountOrCallback) || is_float($amountOrCallback)) {
            $amount = (float) $amountOrCallback;
            $this->assertSent('CheckoutSessions_create', static fn (array $body) => abs((float) ($body['amountUsd'] ?? 0) - $amount) < 0.001);

            return;
        }
        $this->assertSent('CheckoutSessions_create', $amountOrCallback);
    }

    /**
     * @param callable(array<string, mixed>, RecordedRequest): bool|null $callback
     */
    public function assertPayoutCreated(?callable $callback = null): void
    {
        $this->assertSent('Payouts_create', $callback);
    }

    /**
     * @param callable(array<string, mixed>, RecordedRequest): bool|null $callback
     */
    public function assertMerchantCreated(?callable $callback = null): void
    {
        $this->assertSent('Merchants_create', $callback);
    }

    public function __get(string $name): mixed
    {
        return $this->client->{$name};
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->{$name}(...$arguments);
    }

    /**
     * @return list<RecordedRequest>
     */
    private function matching(string $operationId, ?callable $callback): array
    {
        return array_values(array_filter(
            $this->http->recorded($operationId),
            static fn (RecordedRequest $r) => $callback === null || $callback($r->body ?? [], $r),
        ));
    }
}
