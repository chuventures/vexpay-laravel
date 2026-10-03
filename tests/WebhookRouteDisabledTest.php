<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use Illuminate\Support\Facades\Route;

final class WebhookRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('vexpay.webhook.route', false);
    }

    public function testRouteCanBeTurnedOff(): void
    {
        self::assertNull(Route::getRoutes()->getByName('vexpay.webhook'));
        $this->postVexPayWebhook('notification.test')->assertNotFound();
    }
}
