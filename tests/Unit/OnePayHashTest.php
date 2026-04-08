<?php

declare(strict_types=1);

namespace OnePay\Checkout\Tests\Unit;

use OnePay\Checkout\Services\OnePayService;
use OnePay\Checkout\Tests\TestCase;

class OnePayHashTest extends TestCase
{
    public function test_generate_hash_uses_exact_concatenation_order_and_two_decimal_amount(): void
    {
        $service = $this->app->make(OnePayService::class);

        $plain = 'MYAPPID' . 'LKR' . '200.00' . 'mysalt';
        $expected = hash('sha256', $plain);

        $this->assertSame($expected, $service->generateHash('MYAPPID', 'LKR', '200'));
        $this->assertSame($expected, $service->generateHash('MYAPPID', 'LKR', '200.0'));
        $this->assertSame($expected, $service->generateHash('MYAPPID', 'LKR', '200.000'));
        $this->assertSame($expected, $service->generateHash('MYAPPID', 'LKR', 200));
    }

    public function test_generate_hash_output_is_lowercase_hex(): void
    {
        $service = $this->app->make(OnePayService::class);
        $hash = $service->generateHash('MYAPPID', 'LKR', '1.50');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_generate_reference_is_prefixed_and_unique(): void
    {
        $service = $this->app->make(OnePayService::class);

        $a = $service->generateReference('INV');
        $b = $service->generateReference('INV');

        $this->assertStringStartsWith('INV', $a);
        $this->assertNotSame($a, $b);
    }
}
