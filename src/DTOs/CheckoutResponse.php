<?php

declare(strict_types=1);

namespace OnePay\Checkout\DTOs;

use JsonSerializable;

final readonly class CheckoutResponse implements JsonSerializable
{
    public function __construct(
        public string $reference,
        public string $hash,
        public ?string $redirectUrl,
        public array $rawResponse,
    ) {}

    public static function fromApiResponse(string $reference, string $hash, array $decoded): self
    {
        return new self(
            reference: $reference,
            hash: $hash,
            redirectUrl: $decoded['data']['gateway']['redirect_url'] ?? ($decoded['redirect_url'] ?? null),
            rawResponse: $decoded,
        );
    }

    public function succeeded(): bool
    {
        $status = $this->rawResponse['status'] ?? null;

        if ($status === true || $status === 1 || $status === '1') {
            return true;
        }

        if (($this->rawResponse['data']['status'] ?? null) === true) {
            return true;
        }

        // OnePay returns HTTP-style codes in the JSON body (e.g. 200) on success.
        if (is_int($status) && $status >= 200 && $status < 300) {
            return true;
        }

        if (is_string($status) && ctype_digit($status)) {
            $code = (int) $status;

            return $code >= 200 && $code < 300;
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'hash' => $this->hash,
            'redirect_url' => $this->redirectUrl,
            'raw_response' => $this->rawResponse,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
