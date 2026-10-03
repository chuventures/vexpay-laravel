<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use VexPay\Laravel\Billable;

final class Order extends Model
{
    use Billable;

    protected $guarded = [];
}
