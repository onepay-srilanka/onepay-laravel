<?php

declare(strict_types=1);

namespace OnePay\Checkout\Facades;

use Illuminate\Support\Facades\Facade;
use OnePay\Checkout\DTOs\CheckoutResponse;
use OnePay\Checkout\Services\OnePayService;

/**
 * @method static CheckoutResponse createCheckoutLink(array $data)
 * @method static string generateHash(string $appId, string $currency, string|int|float $amount)
 * @method static string generateReference(string $prefix = 'REF')
 *
 * @see OnePayService
 */
class OnePay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OnePayService::class;
    }
}
