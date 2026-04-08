<?php

declare(strict_types=1);

namespace OnePay\Checkout\Tests;

use OnePay\Checkout\OnePayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [OnePayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('onepay.base_url', 'https://api.oneapay.lk/v3');
        $app['config']->set('onepay.app_id', 'MYAPPID');
        $app['config']->set('onepay.app_token', 'secret-token');
        $app['config']->set('onepay.hash_salt', 'mysalt');
        $app['config']->set('onepay.currency', 'LKR');
        $app['config']->set('onepay.timeout', 5);
        $app['config']->set('onepay.retry.times', 1);
        $app['config']->set('onepay.retry.sleep_ms', 10);
    }
}
