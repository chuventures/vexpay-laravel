<?php

declare(strict_types=1);

namespace VexPay\Laravel\Console;

use Illuminate\Console\Command;
use VexPay\Exception\VexPayException;
use VexPay\VexPayClient;

final class WebhookTestCommand extends Command
{
    protected $signature = 'vexpay:webhook-test';

    protected $description = 'Ask VEXPay to send a notification.test webhook to your registered endpoints';

    public function handle(): int
    {
        try {
            $result = $this->laravel->make(VexPayClient::class)->webhookEndpoints->sendTest();
        } catch (VexPayException $e) {
            $this->components->error(sprintf(
                'VEXPay did not accept the request%s: %s',
                $e->getErrorCode() !== null ? ' (' . $e->getErrorCode() . ')' : '',
                $e->getMessage(),
            ));

            return self::FAILURE;
        }

        if (!$result->sent) {
            $this->components->warn('VEXPay accepted the request but sent nothing — register a webhook endpoint in the dashboard first.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'VEXPay is sending %s to your registered webhook endpoints. Watch for the NotificationTest event.',
            $result->event,
        ));

        return self::SUCCESS;
    }
}
