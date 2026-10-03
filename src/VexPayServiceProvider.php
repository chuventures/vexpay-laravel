<?php

declare(strict_types=1);

namespace VexPay\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use VexPay\Exception\ConfigurationException;
use VexPay\Laravel\Console\InstallCommand;
use VexPay\Laravel\Console\WebhookTestCommand;
use VexPay\Laravel\Http\Controllers\WebhookController;
use VexPay\Laravel\Http\Middleware\VerifyWebhookSignature;
use VexPay\Laravel\View\Components\Checkout as CheckoutComponent;
use VexPay\VexPayClient;

final class VexPayServiceProvider extends ServiceProvider
{
    /** Bind a PSR-18 client under this id to route the SDK's HTTP traffic through it. */
    public const HTTP_CLIENT = 'vexpay.http_client';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vexpay.php', 'vexpay');

        $this->app->singleton(VexPayClient::class, static function (Application $app): VexPayClient {
            $config = $app['config']['vexpay'];
            if (blank($config['api_key'] ?? null)) {
                throw new ConfigurationException(
                    'No VEXPay API key configured. Set VEXPAY_API_KEY in your .env (find it in the VEXPay dashboard).',
                );
            }

            return new VexPayClient(array_filter([
                'api_key' => $config['api_key'],
                'base_url' => $config['base_url'] ?? null,
                'timeout' => $config['timeout'] ?? null,
                'max_network_retries' => $config['max_network_retries'] ?? null,
                'http_client' => $app->bound(self::HTTP_CLIENT) ? $app->make(self::HTTP_CLIENT) : null,
            ], static fn ($value) => $value !== null && $value !== ''));
        });
        $this->app->singleton('vexpay', static fn (Application $app) => $app->make(VexPayClient::class));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vexpay');
        Blade::component('vexpay-checkout', CheckoutComponent::class);

        if ($this->app['config']->get('vexpay.webhook.route', true)) {
            $this->registerWebhookRoute();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/vexpay.php' => config_path('vexpay.php')], 'vexpay-config');
            $this->publishes([
                __DIR__ . '/../database/migrations/create_vexpay_payments_table.php' => self::migrationPath('create_vexpay_payments_table'),
            ], 'vexpay-migrations');
            $this->commands([InstallCommand::class, WebhookTestCommand::class]);
        }
    }

    private function registerWebhookRoute(): void
    {
        // Registered outside the `web` group on purpose: no session, no CSRF — the signature is the auth.
        Route::post($this->app['config']->get('vexpay.webhook.path', 'vexpay/webhook'), WebhookController::class)
            ->middleware(array_merge(
                (array) $this->app['config']->get('vexpay.webhook.middleware', []),
                [VerifyWebhookSignature::class],
            ))
            ->name('vexpay.webhook');
    }

    /**
     * Reuse an already-published migration so re-running the installer never duplicates it.
     */
    public static function migrationPath(string $name): string
    {
        $existing = glob(database_path('migrations/*_' . $name . '.php')) ?: [];

        return $existing[0] ?? database_path('migrations/' . date('Y_m_d_His') . '_' . $name . '.php');
    }
}
