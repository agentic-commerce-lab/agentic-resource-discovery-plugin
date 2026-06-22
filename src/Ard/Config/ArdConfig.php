<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Config;

final class ArdConfig
{
    /**
     * @param list<array<string, mixed>> $referrals
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly string $hostDisplayName,
        public readonly ?string $documentationUrl = null,
        public readonly array $referrals = [],
    ) {
    }
}
