<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\HasVexPayMerchant;

final class Seller extends Model
{
    use HasVexPayMerchant;

    protected $guarded = [];
}
