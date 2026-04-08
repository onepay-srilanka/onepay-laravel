<p align="center">
  <strong>Laravel · OnePay · Checkout Link API</strong>
</p>

# OnePay Checkout for Laravel

**Server-side Laravel integration for the OnePay Checkout Link API** (`api.oneapay.lk`) — create payment links with correct SHA-256 hashing, validation, and structured error handling.

<p align="center">
  <a href="https://packagist.org/packages/onepay/laravel-checkout"><img src="https://img.shields.io/packagist/v/onepay/laravel-checkout?label=stable&style=flat-square" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/onepay/laravel-checkout"><img src="https://img.shields.io/packagist/dt/onepay/laravel-checkout?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/onepay/laravel-checkout"><img src="https://img.shields.io/packagist/dm/onepay/laravel-checkout?style=flat-square" alt="Monthly Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/onepay/laravel-checkout?style=flat-square" alt="License"></a>
</p>

---

## Getting started

The steps below work with **Laravel 10.x, 11.x, and 12.x** (PHP **8.1+**).

Laravel **auto-discovers** the package: you do not need to register the service provider manually unless you disabled discovery.

---

## Install

Install the package with Composer:

```bash
composer require onepay/laravel-checkout
```

---

## Configure

Publish the configuration file:

```bash
php artisan vendor:publish --tag=onepay-config
```

This creates `config/onepay.php`. Set your **server-side only** secrets in `.env` (never expose these in frontend or mobile apps):

```env
ONEPAY_APP_ID=your-app-id-here
ONEPAY_APP_TOKEN=your-app-token-here
ONEPAY_HASH_SALT=your-hash-salt-here
ONEPAY_CURRENCY=LKR
ONEPAY_TIMEOUT=30
ONEPAY_RETRY_TIMES=3
ONEPAY_RETRY_SLEEP_MS=500
```

The **API base URL** is fixed in `config/onepay.php` (`https://api.oneapay.lk/v3`) and is not read from `.env`, so it cannot be overridden from client input.

---

## Usage

### Dependency injection (recommended)

Inject `OnePay\Checkout\Services\OnePayService` into your controller or action:

```php
use OnePay\Checkout\Services\OnePayService;
use OnePay\Checkout\Exceptions\OnePayException;

public function pay(OnePayService $onePay)
{
    try {
        $response = $onePay->createCheckoutLink([
            'amount' => 200.00,
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'customer_phone_number' => '+94771234567',
            'customer_email' => 'john@example.com',
            'transaction_redirect_url' => 'https://yoursite.test/payment/return',
            // 'reference' => 'OPTIONAL_CUSTOM_REF', // omit to auto-generate
            // 'currency' => 'LKR',                  // optional; default from config
        ]);

        if (! $response->succeeded()) {
            // Handle logical failure using $response->rawResponse
        }

        return redirect()->away($response->redirectUrl);
    } catch (OnePayException $e) {
        if ($e->hasRemoteErrorPayload()) {
            // OnePay JSON: message + error — see $e->getRemoteMessage(), getRemoteError()
        }
        throw $e;
    }
}
```

### Facade

```php
use OnePay\Checkout\Facades\OnePay;

$response = OnePay::createCheckoutLink([
    'amount' => 1500,
    'customer_first_name' => 'Jane',
    'customer_last_name' => 'Doe',
    'customer_phone_number' => '+94770000000',
    'customer_email' => 'jane@example.com',
    'transaction_redirect_url' => 'https://yoursite.test/done',
]);
```

### Response object

`createCheckoutLink()` returns `OnePay\Checkout\DTOs\CheckoutResponse`:

| Property / method   | Description                                      |
|--------------------|--------------------------------------------------|
| `reference`        | Payment reference (yours or auto-generated)      |
| `hash`             | SHA-256 sent to the API                          |
| `redirectUrl`      | Gateway URL to send the customer to              |
| `rawResponse`      | Decoded JSON from OnePay                         |
| `succeeded()`      | Helper for success-style payloads                |
| `toArray()`        | Array for JSON APIs                              |

### Hash rules (OnePay requirement)

The package normalises **amount to two decimal places** and builds:

`sha256(app_id + currency + amount + hash_salt)` → **lowercase** hex.

---

## Laravel version compatibility

| Laravel | PHP    | Package status   |
|---------|--------|------------------|
| 12.x    | ≥ 8.1  | Supported        |
| 11.x    | ≥ 8.1  | Supported        |
| 10.x    | ≥ 8.1  | Supported        |
| 9.x     | —      | Not supported    |

---

## Security

- Keep `ONEPAY_APP_TOKEN` and `ONEPAY_HASH_SALT` **only** on the server.
- Validate and **allowlist** `transaction_redirect_url` if it can be influenced by end users (open-redirect risk).
- Confirm paid orders using **OnePay’s official** callback / status flows — this package covers **checkout link creation** only.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## Links

- [Packagist — `onepay/laravel-checkout`](https://packagist.org/packages/onepay/laravel-checkout)
- OnePay API base: `https://api.oneapay.lk/v3/checkout/link/`
