<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\AgenticCommerce;

final class InMemoryAgenticCommerceBridge implements AgenticCommerceBridgeInterface
{
    public function __construct(
        private readonly bool $available,
        private readonly AgenticCommerceResourceState $state,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function resolve(string $baseUrl, mixed $salesChannelContext): AgenticCommerceResourceState
    {
        return $this->state;
    }
}
