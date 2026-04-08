<?php

declare(strict_types=1);

namespace OnePay\Checkout\Tests\Unit;

use OnePay\Checkout\DTOs\CheckoutResponse;
use PHPUnit\Framework\TestCase;

class CheckoutResponseTest extends TestCase
{
    public function test_succeeded_true_when_json_status_is_200(): void
    {
        $raw = [
            'status' => 200,
            'message' => 'Successfully generate checkout link',
            'data' => [
                'gateway' => [
                    'redirect_url' => 'https://gateway.onepay.lk/checkout/x',
                ],
            ],
        ];

        $r = CheckoutResponse::fromApiResponse('REF12345678', 'hash', $raw);

        $this->assertTrue($r->succeeded());
        $this->assertSame('https://gateway.onepay.lk/checkout/x', $r->redirectUrl);
    }

    public function test_succeeded_true_for_legacy_boolean_status(): void
    {
        $raw = ['status' => true, 'data' => ['gateway' => ['redirect_url' => 'https://example.test/pay']]];

        $r = CheckoutResponse::fromApiResponse('REF12345678', 'hash', $raw);

        $this->assertTrue($r->succeeded());
    }
}
