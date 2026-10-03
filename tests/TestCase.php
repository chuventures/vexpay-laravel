<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use VexPay\Laravel\Facades\VexPay;
use VexPay\Laravel\Testing\InteractsWithVexPay;
use VexPay\Laravel\VexPayServiceProvider;

abstract class TestCase extends Orchestra
{
    use InteractsWithVexPay;
    use RefreshDatabase;

    public const API_KEY = 'sk_test_laravel_suite_key';
    public const WEBHOOK_SECRET = 'whsec_laravel_suite_secret';

    protected function getPackageProviders($app): array
    {
        return [VexPayServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['VexPay' => VexPay::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('vexpay.api_key', self::API_KEY);
        $app['config']->set('vexpay.webhook.secret', self::WEBHOOK_SECRET);
        $app['config']->set('vexpay.base_url', 'https://api.vexpay.test');
        $app['config']->set('vexpay.merchant_models', [Fixtures\Seller::class]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/migrations');
    }
}
