<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use VexPay\Laravel\Events\Webhooks\PayoutFailed;

final class QueuedPayoutListener implements ShouldQueue
{
    public function handle(PayoutFailed $event): void
    {
    }
}
