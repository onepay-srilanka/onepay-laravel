<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OnePay\Checkout\Exceptions\OnePayException;
use OnePay\Checkout\Services\OnePayService;

/**
 * Example controller demonstrating OnePay checkout integration.
 * Copy this into your application and adjust as needed.
 */
class OnePayController extends Controller
{
    public function __construct(
        private readonly OnePayService $onePay,
    ) {}

    /**
     * Create a checkout link and return the redirect URL.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'min:10', 'max:64'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'redirect_url' => ['required', 'url', 'max:2048'],
            'additional_data' => ['nullable', 'string', 'max:65535'],
            'items' => ['nullable', 'array', 'max:500'],
            'items.*' => ['string', 'max:255'],
        ]);

        $reference = $validated['reference'] ?? $this->onePay->generateReference('ORD');

        try {
            $payload = [
                'reference' => $reference,
                'amount' => $validated['amount'],
                'customer_first_name' => $validated['first_name'],
                'customer_last_name' => $validated['last_name'],
                'customer_phone_number' => $validated['phone'],
                'customer_email' => $validated['email'],
                'transaction_redirect_url' => $validated['redirect_url'],
            ];

            if (! empty($validated['additional_data'])) {
                $payload['additionalData'] = $validated['additional_data'];
            }

            if (! empty($validated['items'])) {
                $payload['items'] = $validated['items'];
            }

            $response = $this->onePay->createCheckoutLink($payload);

            if (! $response->succeeded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway did not return a success status.',
                    'details' => $response->rawResponse,
                ], 502);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $response->redirectUrl,
                'reference' => $response->reference,
            ]);
        } catch (OnePayException $e) {
            report($e);

            $httpStatus = $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            if ($e->hasRemoteErrorPayload()) {
                return response()->json([
                    'status' => $httpStatus,
                    'message' => $e->getRemoteMessage() ?? $e->getMessage(),
                    'error' => $e->getRemoteError(),
                ], $httpStatus);
            }

            if ($e->getCode() === 422) {
                return response()->json([
                    'status' => 422,
                    'message' => $e->getMessage(),
                    'errors' => $e->getContext()['validation_errors'] ?? [],
                ], 422);
            }

            return response()->json([
                'status' => $httpStatus,
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
            ], $httpStatus);
        }
    }
}
