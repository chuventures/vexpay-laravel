<?php

declare(strict_types=1);

namespace VexPay\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A checkout started through the Billable trait, kept in sync from payment.* webhooks.
 *
 * @property int|string $id
 * @property string $billable_type
 * @property int|string $billable_id
 * @property string $checkout_session_id
 * @property string|null $payment_id
 * @property string $reference
 * @property string $amount_usd
 * @property string|null $amount_ves
 * @property string|null $bcv_rate
 * @property string|null $method
 * @property string $status
 * @property string|null $failure_code
 * @property bool $livemode
 * @property array<string, string>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Payment extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELED = 'CANCELED';
    public const STATUS_REVERSED = 'REVERSED';

    protected $guarded = [];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'amount_ves' => 'decimal:2',
        'bcv_rate' => 'decimal:8',
        'livemode' => 'boolean',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('vexpay.payments.table', 'vexpay_payments');
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }
}
