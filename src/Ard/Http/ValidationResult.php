<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Http;

final class ValidationResult
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public readonly bool $valid,
        public readonly array $payload = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function valid(array $payload): self
    {
        return new self(true, $payload);
    }

    public static function invalid(string $message, string $errorCode = 'INVALID_ARGUMENT'): self
    {
        return new self(false, [], $errorCode, $message);
    }
}
