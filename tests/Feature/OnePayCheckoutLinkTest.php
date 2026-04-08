<?php

declare(strict_types=1);

namespace OnePay\Checkout\Tests\Feature;

use Illuminate\Support\Facades\Http;
use OnePay\Checkout\Exceptions\OnePayException;
use OnePay\Checkout\Services\OnePayService;
use OnePay\Checkout\Tests\TestCase;

class OnePayCheckoutLinkTest extends TestCase
{
    /**
     * Minimal valid customer fields (aligned across tests so validation always passes
     * before HTTP fakes are hit).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'reference' => 'REF-PAYLOAD-001',
            'currency' => 'LKR',
            'amount' => 100,
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'customer_phone_number' => '+94771234567',
            'customer_email' => 'john@example.com',
            'transaction_redirect_url' => 'https://example.com/done',
        ], $overrides);
    }

    public function test_create_checkout_link_posts_expected_payload_and_maps_response(): void
    {
        Http::fake([
            'https://api.onepay.lk/v3/checkout/link*' => Http::response([
                'status' => true,
                'data' => [
                    'gateway' => [
                        'redirect_url' => 'https://gateway.test/pay',
                    ],
                ],
            ], 200),
        ]);

        $service = $this->app->make(OnePayService::class);

        $result = $service->createCheckoutLink($this->checkoutPayload([
            'amount' => 200,
            'reference' => 'REFTEST123',
            'additionalData' => 'order_meta=abc',
            'items' => ['item-1', 42],
        ]));

        $expectedHash = hash('sha256', 'MYAPPIDLKR200.00mysalt');

        $this->assertSame('REFTEST123', $result->reference);
        $this->assertSame($expectedHash, $result->hash);
        $this->assertSame('https://gateway.test/pay', $result->redirectUrl);
        $this->assertTrue($result->succeeded());

        Http::assertSent(function ($request) use ($expectedHash) {
            $data = $request->data();

            return $request->url() === 'https://api.onepay.lk/v3/checkout/link/'
                && $request->hasHeader('Authorization', 'secret-token')
                && ($data['currency'] ?? null) === 'LKR'
                && ($data['app_id'] ?? null) === 'MYAPPID'
                && ($data['hash'] ?? null) === $expectedHash
                && ($data['amount'] ?? null) === '200.00'
                && ($data['reference'] ?? null) === 'REFTEST123'
                && ($data['customer_email'] ?? null) === 'john@example.com'
                && ($data['additionalData'] ?? null) === 'order_meta=abc'
                && ($data['items'] ?? null) === ['item-1', '42'];
        });
    }

    public function test_create_checkout_link_throws_on_api_error_plain_body(): void
    {
        Http::fake([
            'https://api.onepay.lk/v3/checkout/link*' => Http::response('Bad Request', 400),
        ]);

        $service = $this->app->make(OnePayService::class);

        $this->expectException(OnePayException::class);
        $this->expectExceptionMessage('OnePay API returned HTTP 400.');

        $service->createCheckoutLink($this->checkoutPayload());
    }

    public function test_create_checkout_link_parses_onepay_json_error_body(): void
    {
        Http::fake([
            'https://api.onepay.lk/v3/checkout/link*' => Http::response([
                'status' => 400,
                'message' => 'Invalid request data',
                'error' => 'Invalid app credentials',
            ], 400),
        ]);

        $service = $this->app->make(OnePayService::class);

        try {
            $service->createCheckoutLink($this->checkoutPayload());
            $this->fail('Expected OnePayException to be thrown.');
        } catch (OnePayException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertSame('Invalid request data: Invalid app credentials', $e->getMessage());
            $this->assertSame('Invalid request data', $e->getRemoteMessage());
            $this->assertSame('Invalid app credentials', $e->getRemoteError());
            $this->assertTrue($e->hasRemoteErrorPayload());
        }
    }

    public function test_create_checkout_link_validation_fails_for_invalid_email(): void
    {
        Http::fake();

        $service = $this->app->make(OnePayService::class);

        $this->expectException(OnePayException::class);
        $this->expectExceptionMessage('Checkout payload validation failed.');

        try {
            $service->createCheckoutLink([
                'reference' => 'REF-VAL-EMAIL',
                'currency' => 'LKR',
                'amount' => 50,
                'customer_first_name' => 'X',
                'customer_last_name' => 'Y',
                'customer_phone_number' => '+94770000000',
                'customer_email' => 'not-an-email',
                'transaction_redirect_url' => 'https://example.com/cb',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_create_checkout_link_validation_fails_without_currency(): void
    {
        Http::fake();

        $service = $this->app->make(OnePayService::class);

        $this->expectException(OnePayException::class);
        $this->expectExceptionMessage('Checkout payload validation failed.');

        try {
            $service->createCheckoutLink([
                'reference' => 'REF-NOCURRENCY',
                'amount' => 10,
                'customer_first_name' => 'X',
                'customer_last_name' => 'Y',
                'customer_phone_number' => '+94770000000',
                'customer_email' => 'x@example.com',
                'transaction_redirect_url' => 'https://example.com/cb',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_create_checkout_link_validation_fails_without_reference(): void
    {
        Http::fake();

        $service = $this->app->make(OnePayService::class);

        $this->expectException(OnePayException::class);
        $this->expectExceptionMessage('Checkout payload validation failed.');

        try {
            $service->createCheckoutLink([
                'currency' => 'LKR',
                'amount' => 10,
                'customer_first_name' => 'X',
                'customer_last_name' => 'Y',
                'customer_phone_number' => '+94770000000',
                'customer_email' => 'x@example.com',
                'transaction_redirect_url' => 'https://example.com/cb',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_create_checkout_link_validation_fails_when_reference_too_short(): void
    {
        Http::fake();

        $service = $this->app->make(OnePayService::class);

        $this->expectException(OnePayException::class);
        $this->expectExceptionMessage('Checkout payload validation failed.');

        try {
            $service->createCheckoutLink([
                'reference' => 'SHORTREF', // 8 characters
                'currency' => 'LKR',
                'amount' => 10,
                'customer_first_name' => 'X',
                'customer_last_name' => 'Y',
                'customer_phone_number' => '+94770000000',
                'customer_email' => 'x@example.com',
                'transaction_redirect_url' => 'https://example.com/cb',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }
}
