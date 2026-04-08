<?php

declare(strict_types=1);

namespace OnePay\Checkout\Exceptions;

use RuntimeException;
use Throwable;

class OnePayException extends RuntimeException
{
    protected array $context;

    public function __construct(
        string $message,
        int $code = 0,
        array $context = [],
        ?Throwable $previous = null,
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function configMissing(string $key): static
    {
        return new static(
            "OnePay configuration value [{$key}] is missing. "
            . 'Publish the config and set the corresponding env variable.',
        );
    }

    /**
     * Build an exception from a non-success HTTP response.
     *
     * When the body is JSON with "message" / "error" (OnePay format), those
     * values are exposed via getRemoteMessage() / getRemoteError() and folded
     * into getMessage() for logging and generic handlers.
     */
    public static function apiError(int $httpStatus, string $body): static
    {
        $context = ['response_body' => $body];

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            return new static(
                "OnePay API returned HTTP {$httpStatus}.",
                $httpStatus,
                $context,
            );
        }

        $context['raw_response'] = $decoded;

        $remoteMessage = self::stringOrNull($decoded['message'] ?? null);
        $remoteError = self::stringOrNull($decoded['error'] ?? null);

        if ($remoteMessage !== null) {
            $context['remote_message'] = $remoteMessage;
        }
        if ($remoteError !== null) {
            $context['remote_error'] = $remoteError;
        }

        $summary = self::buildRemoteSummary($httpStatus, $remoteMessage, $remoteError);

        return new static($summary, $httpStatus, $context);
    }

    /**
     * OnePay / gateway "message" field from the response body, if JSON was parsed.
     */
    public function getRemoteMessage(): ?string
    {
        $v = $this->context['remote_message'] ?? null;

        return is_string($v) ? $v : null;
    }

    /**
     * OnePay / gateway "error" field from the response body, if JSON was parsed.
     */
    public function getRemoteError(): ?string
    {
        $v = $this->context['remote_error'] ?? null;

        return is_string($v) ? $v : null;
    }

    public function hasRemoteErrorPayload(): bool
    {
        return $this->getRemoteMessage() !== null || $this->getRemoteError() !== null;
    }

    private static function buildRemoteSummary(
        int $httpStatus,
        ?string $remoteMessage,
        ?string $remoteError,
    ): string {
        if ($remoteMessage !== null && $remoteError !== null) {
            return "{$remoteMessage}: {$remoteError}";
        }
        if ($remoteError !== null) {
            return $remoteError;
        }
        if ($remoteMessage !== null) {
            return $remoteMessage;
        }

        return "OnePay API returned HTTP {$httpStatus}.";
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    public static function requestFailed(Throwable $exception): static
    {
        return new static(
            'OnePay API request failed: ' . $exception->getMessage(),
            (int) $exception->getCode(),
            [],
            $exception,
        );
    }

    public static function validationFailed(array $errors): static
    {
        return new static(
            'Checkout payload validation failed.',
            422,
            ['validation_errors' => $errors],
        );
    }
}
