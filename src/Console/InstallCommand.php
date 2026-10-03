<?php

declare(strict_types=1);

namespace VexPay\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use VexPay\Laravel\VexPayServiceProvider;

final class InstallCommand extends Command
{
    protected $signature = 'vexpay:install
        {--merchant-table= : Also publish a migration adding vexpay_merchant_id to this table (for HasVexPayMerchant)}';

    protected $description = 'Publish the VEXPay config and migrations and print the remaining setup steps';

    public function handle(Filesystem $files): int
    {
        $this->callSilently('vendor:publish', ['--tag' => 'vexpay-config']);
        $this->callSilently('vendor:publish', ['--tag' => 'vexpay-migrations']);
        $this->components->info('Published config/vexpay.php and the vexpay_payments migration.');

        $table = $this->option('merchant-table');
        if (is_string($table) && $table !== '') {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                $this->components->error('The merchant table name may only contain letters, numbers and underscores.');

                return self::FAILURE;
            }
            $path = VexPayServiceProvider::migrationPath('add_vexpay_merchant_id_to_' . $table . '_table');
            if (!$files->exists($path)) {
                $stub = $files->get(__DIR__ . '/../../database/stubs/add_vexpay_merchant_id_column.php.stub');
                $files->put($path, str_replace('{{ table }}', $table, $stub));
            }
            $this->components->info(sprintf('Published a migration adding vexpay_merchant_id to "%s".', $table));
        }

        $this->newLine();
        $this->line('  <options=bold>Next steps</>');
        $this->newLine();
        $this->line('  1. Add your keys to .env (from the VEXPay dashboard — use the test-mode key while building):');
        $this->newLine();
        $this->line('       VEXPAY_API_KEY=');
        $this->line('       VEXPAY_WEBHOOK_SECRET=');
        $this->newLine();
        $this->line('  2. Run the migrations:  php artisan migrate');
        $this->newLine();
        $this->line('  3. Register this webhook URL in the VEXPay dashboard (Webhooks):');
        $this->newLine();
        $this->line('       ' . url((string) config('vexpay.webhook.path', 'vexpay/webhook')));
        $this->newLine();
        $this->line('  4. Fulfil orders from the PaymentSucceeded event (or WebhookHandled), never from browser events.');
        $this->newLine();
        $this->line('  Then check delivery with:  php artisan vexpay:webhook-test');

        return self::SUCCESS;
    }
}
