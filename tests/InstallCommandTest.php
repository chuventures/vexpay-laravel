<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Filesystem\Filesystem;

final class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $files = new Filesystem();
        $files->delete(config_path('vexpay.php'));
        $files->delete(glob(database_path('migrations/*vexpay*.php')) ?: []);
        parent::tearDown();
    }

    public function testPublishesConfigAndMigrationsAndPrintsNextSteps(): void
    {
        $this->artisan('vexpay:install')
            ->expectsOutputToContain('VEXPAY_API_KEY=')
            ->expectsOutputToContain('VEXPAY_WEBHOOK_SECRET=')
            ->expectsOutputToContain(url('vexpay/webhook'))
            ->doesntExpectOutputToContain(self::API_KEY)
            ->doesntExpectOutputToContain(self::WEBHOOK_SECRET)
            ->assertSuccessful();

        self::assertFileExists(config_path('vexpay.php'));
        $migrations = glob(database_path('migrations/*_create_vexpay_payments_table.php')) ?: [];
        self::assertCount(1, $migrations);
        self::assertStringNotContainsString(self::API_KEY, (string) file_get_contents(config_path('vexpay.php')));
    }

    public function testRunningTwiceDoesNotDuplicateTheMigration(): void
    {
        $this->artisan('vexpay:install')->assertSuccessful();
        $this->travel(5)->seconds();
        $this->artisan('vexpay:install')->assertSuccessful();

        self::assertCount(1, glob(database_path('migrations/*_create_vexpay_payments_table.php')) ?: []);
    }

    public function testMerchantTableOptionPublishesTheColumnMigration(): void
    {
        $this->artisan('vexpay:install', ['--merchant-table' => 'sellers'])->assertSuccessful();

        $migrations = glob(database_path('migrations/*_add_vexpay_merchant_id_to_sellers_table.php')) ?: [];
        self::assertCount(1, $migrations);
        self::assertStringContainsString("Schema::table('sellers'", (string) file_get_contents($migrations[0]));
    }

    public function testRejectsUnsafeTableNames(): void
    {
        $this->artisan('vexpay:install', ['--merchant-table' => 'sellers; drop'])->assertFailed();
    }
}
