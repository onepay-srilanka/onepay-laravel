<?php

declare(strict_types=1);

namespace OnePay\Checkout;

use Illuminate\Support\ServiceProvider;
use OnePay\Checkout\Services\OnePayService;

class OnePayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/onepay.php', 'onepay');

        $this->app->singleton(OnePayService::class, fn () => new OnePayService());

        $this->app->alias(OnePayService::class, 'onepay');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/onepay.php' => config_path('onepay.php'),
            ], 'onepay-config');
        }
    }
}
