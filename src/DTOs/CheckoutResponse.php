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

        return $status === true
            || $status === 1
            || $status === '1'
            || ($this->rawResponse['data']['status'] ?? null) === true;
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
