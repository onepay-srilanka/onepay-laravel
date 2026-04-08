<?php

declare(strict_types=1);

namespace OnePay\Checkout\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OnePay\Checkout\DTOs\CheckoutResponse;
use OnePay\Checkout\Exceptions\OnePayException;

class OnePayService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appToken;
    protected string $hashSalt;
    protected string $currency;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleepMs;

    public function __construct()
    {
        $this->baseUrl = $this->requireConfig('onepay.base_url');
        $this->appId = $this->requireConfig('onepay.app_id');
        $this->appToken = $this->requireConfig('onepay.app_token');
        $this->hashSalt = $this->requireConfig('onepay.hash_salt');
        $this->currency = config('onepay.currency', 'LKR');
        $this->timeout = (int) config('onepay.timeout', 30);
        $this->retryTimes = (int) config('onepay.retry.times', 3);
        $this->retrySleepMs = (int) config('onepay.retry.sleep_ms', 500);
    }

    // ------------------------------------------------------------------
    //  Public API
    // ------------------------------------------------------------------

    /**
     * Create a OnePay checkout link and return a structured response.
     *
     * @param  array{
     *     amount: string|int|float,
     *     reference?: string,
     *     customer_first_name: string,
     *     customer_last_name: string,
     *     customer_phone_number: string,
     *     customer_email: string,
     *     transaction_redirect_url: string,
     *     currency?: string,
     * } $data
     *
     * @throws OnePayException
     */
    public function createCheckoutLink(array $data): CheckoutResponse
    {
        $data = $this->validatePayload($data);

        $currency = $data['currency'] ?? $this->currency;
        $amount = $this->normalizeAmount($data['amount']);
        $reference = $data['reference'] ?? $this->generateReference();
        $hash = $this->generateHash($this->appId, $currency, $amount);

        $payload = [
            'currency' => $currency,
            'app_id' => $this->appId,
            'hash' => $hash,
            'amount' => $amount,
            'reference' => $reference,
            'customer_first_name' => $data['customer_first_name'],
            'customer_last_name' => $data['customer_last_name'],
            'customer_phone_number' => $data['customer_phone_number'],
            'customer_email' => $data['customer_email'],
            'transaction_redirect_url' => $data['transaction_redirect_url'],
        ];

        $decoded = $this->post('/checkout/link/', $payload);

        return CheckoutResponse::fromApiResponse($reference, $hash, $decoded);
    }

    /**
     * Generate the SHA-256 hash required by OnePay.
     *
     * Concatenation order: app_id + currency + amount + hash_salt
     * Amount is always normalised to 2 decimal places.
     */
    public function generateHash(string $appId, string $currency, string|int|float $amount): string
    {
        $amount = $this->normalizeAmount($amount);

        $plain = $appId . $currency . $amount . $this->hashSalt;

        return hash('sha256', $plain);
    }

    /**
     * Generate a unique, URL-safe reference string.
     */
    public function generateReference(string $prefix = 'REF'): string
    {
        return $prefix . strtoupper(Str::ulid()->toBase32());
    }

    // ------------------------------------------------------------------
    //  HTTP Transport
    // ------------------------------------------------------------------

    /**
     * @throws OnePayException
     */
    protected function post(string $uri, array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');

        try {
            $response = Http::withHeaders([
                    'Authorization' => $this->appToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs, fn ($exception) => $exception instanceof ConnectionException)
                ->post($url, $payload);

            if ($response->failed()) {
                throw OnePayException::apiError(
                    $response->status(),
                    $response->body(),
                );
            }

            return $response->json() ?? [];
        } catch (OnePayException $e) {
            throw $e;
        } catch (RequestException|ConnectionException $e) {
            throw OnePayException::requestFailed($e);
        }
    }

    // ------------------------------------------------------------------
    //  Validation
    // ------------------------------------------------------------------

    /**
     * Validate and sanitise the incoming checkout data.
     *
     * @throws OnePayException
     */
    protected function validatePayload(array $data): array
    {
        $validator = Validator::make($data, [
            'amount' => ['required', 'numeric', 'gt:0'],
            'customer_first_name' => ['required', 'string', 'max:255'],
            'customer_last_name' => ['required', 'string', 'max:255'],
            'customer_phone_number' => ['required', 'string', 'max:20'],
            // RFC only — avoid dns: it requires network and breaks CI/sandbox runs.
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'transaction_redirect_url' => ['required', 'url', 'max:2048'],
            'reference' => ['sometimes', 'string', 'max:64'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        if ($validator->fails()) {
            throw OnePayException::validationFailed(
                $validator->errors()->toArray(),
            );
        }

        return $validator->validated();
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Normalise any numeric input to a string with exactly 2 decimal places.
     */
    protected function normalizeAmount(string|int|float $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * @throws OnePayException
     */
    protected function requireConfig(string $key): string
    {
        $value = config($key);

        if ($value === null || $value === '') {
            throw OnePayException::configMissing($key);
        }

        return (string) $value;
    }
}
