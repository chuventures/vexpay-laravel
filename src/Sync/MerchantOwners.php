<?php

declare(strict_types=1);

namespace VexPay\Laravel\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use VexPay\Laravel\HasVexPayMerchant;

/**
 * Finds the Eloquent model a VEXPay merchant was created from.
 */
final class MerchantOwners
{
    public static function resolve(?string $merchantId, ?string $merchantExternalRef): ?Model
    {
        $fromRef = self::fromExternalRef($merchantExternalRef, $merchantId);
        if ($fromRef !== null) {
            return $fromRef;
        }
        if ($merchantId === null || $merchantId === '') {
            return null;
        }
        foreach ((array) config('vexpay.merchant_models', []) as $class) {
            $owner = $class::query()->where('vexpay_merchant_id', $merchantId)->first();
            if ($owner !== null) {
                return $owner;
            }
        }

        return null;
    }

    /**
     * Refs created by HasVexPayMerchant look like "<morph alias>:<key>".
     */
    private static function fromExternalRef(?string $ref, ?string $merchantId): ?Model
    {
        if ($ref === null || !str_contains($ref, ':')) {
            return null;
        }
        [$alias, $key] = explode(':', $ref, 2);
        $class = Relation::getMorphedModel($alias) ?? $alias;
        if (!class_exists($class) || !in_array(HasVexPayMerchant::class, class_uses_recursive($class), true)) {
            return null;
        }
        $owner = $class::query()->find($key);
        if ($owner === null) {
            return null;
        }
        $stored = $owner->getAttribute('vexpay_merchant_id');

        return $merchantId === null || $stored === null || $stored === $merchantId ? $owner : null;
    }
}
