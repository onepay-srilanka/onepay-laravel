<?php

/**
 * Quick facade-based usage examples.
 * These snippets can be used anywhere in your Laravel application.
 */

use OnePay\Checkout\Facades\OnePay;

// 1. Create a checkout link
$response = OnePay::createCheckoutLink([
    'reference' => 'INV01HXABC', // required — use your order id or OnePay::generateReference()
    'amount' => 1500,
    'customer_first_name' => 'John',
    'customer_last_name' => 'Doe',
    'customer_phone_number' => '+94771234567',
    'customer_email' => 'john@example.com',
    'transaction_redirect_url' => 'https://example.com/payment/callback',
    // optional:
    // 'additionalData' => 'any string metadata for the transaction',
    // 'items' => ['item_id_1', 'item_id_2'],
]);

// Access response properties
$response->redirectUrl;   // Gateway redirect URL
$response->reference;     // Unique payment reference
$response->hash;          // SHA-256 hash used for this request
$response->rawResponse;   // Full decoded API response
$response->succeeded();   // Boolean — did the gateway accept the request?

// 2. Generate a hash manually (useful for verification)
$hash = OnePay::generateHash('APP123', 'LKR', '200.00');

// 3. Generate a unique reference
$ref = OnePay::generateReference('INV');
