<?php

declare(strict_types=1);

namespace VexPay\Laravel;

use Illuminate\Support\Str;
use VexPay\Exception\ConflictException;
use VexPay\Generated\Models as M;
use VexPay\VexPayClient;

/**
 * Sellers on your platform as VEXPay merchants. Needs a nullable `vexpay_merchant_id`
 * column (`php artisan vexpay:install --merchant-table=<table>` publishes the migration).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasVexPayMerchant
{
    /**
     * Create the VEXPay merchant for this model and store its id. Safe to retry: the external
     * reference is stable, and a merchant that already exists for it is adopted.
     *
     * @param array<string, mixed> $params name, identification, contactEmail, contactPhone, bankCode, phone
     */
    public function createAsVexPayMerchant(array $params): M\MerchantResponseDto
    {
        if ($this->hasVexPayMerchant()) {
            throw new \LogicException(sprintf(
                '%s [%s] is already VEXPay merchant %s.',
                static::class,
                $this->getKey(),
                $this->vexpayMerchantId(),
            ));
        }

        $externalRef = (string) ($params['externalRef'] ?? $this->vexpayMerchantExternalRef());
        $merchants = $this->vexpay()->merchants;
        try {
            $merchant = $merchants->create(['externalRef' => $externalRef] + $params);
        } catch (ConflictException) {
            $merchant = $merchants->retrieveByRef($externalRef);
        }

        $this->forceFill(['vexpay_merchant_id' => $merchant->merchantId])->save();

        return $merchant;
    }

    /**
     * "<morph alias>:<key>" — register a morph map to keep it short; refs over 64 chars use a hash.
     */
    public function vexpayMerchantExternalRef(): string
    {
        $ref = $this->getMorphClass() . ':' . $this->getKey();

        return strlen($ref) <= 64 ? $ref : 'm' . substr(hash('sha256', $this->getMorphClass()), 0, 16) . ':' . $this->getKey();
    }

    public function vexpayMerchantId(): ?string
    {
        $id = $this->getAttribute('vexpay_merchant_id');

        return $id === null || $id === '' ? null : (string) $id;
    }

    public function hasVexPayMerchant(): bool
    {
        return $this->vexpayMerchantId() !== null;
    }

    public function asVexPayMerchant(): M\MerchantResponseDto
    {
        return $this->vexpay()->merchants->retrieve($this->requireVexPayMerchantId());
    }

    public function vexpayMerchantStatus(): string
    {
        $status = $this->asVexPayMerchant()->status;

        return $status instanceof \BackedEnum ? (string) $status->value : $status;
    }

    public function vexpayBalance(): M\MerchantBalanceDto
    {
        return $this->vexpay()->merchants->retrieveBalance($this->requireVexPayMerchantId());
    }

    public function vexpayPayoutMethods(): M\PayoutMethodListResponseDto
    {
        return $this->vexpay()->merchants->payoutMethods->list($this->requireVexPayMerchantId());
    }

    /**
     * @param array<string, mixed> $params e.g. bankCode, phone
     */
    public function addVexPayPayoutMethod(array $params): M\PayoutMethodResponseDto
    {
        return $this->vexpay()->merchants->payoutMethods->create($this->requireVexPayMerchantId(), $params);
    }

    public function startVexPayPayoutMethodVerification(string $payoutMethodId): M\VerifyStartResponseDto
    {
        return $this->vexpay()->merchants->payoutMethods->startVerification($this->requireVexPayMerchantId(), $payoutMethodId);
    }

    /**
     * @param array<string, mixed> $params e.g. the micro-deposit amount the seller received
     */
    public function confirmVexPayPayoutMethodVerification(string $payoutMethodId, array $params): M\VerifyConfirmResponseDto
    {
        return $this->vexpay()->merchants->payoutMethods->confirmVerification($this->requireVexPayMerchantId(), $payoutMethodId, $params);
    }

    /**
     * Pay out VES to this seller. `$externalRef` is the payout's idempotency key: retrying with
     * the same reference never creates a second payout. Pass your own (e.g. "order-991") so it
     * holds across processes; a random one is generated otherwise.
     *
     * @param string|int|float     $monto  VES amount; numbers are formatted to two decimals
     * @param array<string, mixed> $params payoutMethodId, paymentId, fromMerchantBalance
     */
    public function payout(string|int|float $monto, string $concepto, ?string $externalRef = null, array $params = []): M\PayoutResponseDto
    {
        $externalRef ??= 'payout_' . Str::ulid();

        return $this->vexpay()->payouts->create(
            array_merge($params, [
                'merchantId' => $this->requireVexPayMerchantId(),
                'monto' => is_string($monto) ? $monto : number_format((float) $monto, 2, '.', ''),
                'concepto' => $concepto,
                'externalRef' => $externalRef,
            ]),
            ['idempotency_key' => 'payout-' . $externalRef],
        );
    }

    protected function requireVexPayMerchantId(): string
    {
        return $this->vexpayMerchantId() ?? throw new \LogicException(sprintf(
            '%s [%s] is not a VEXPay merchant yet. Call createAsVexPayMerchant() first.',
            static::class,
            $this->getKey(),
        ));
    }

    private function vexpay(): VexPayClient
    {
        return app(VexPayClient::class);
    }
}
