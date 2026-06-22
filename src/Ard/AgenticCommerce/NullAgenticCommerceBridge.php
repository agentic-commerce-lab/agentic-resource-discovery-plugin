<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\AgenticCommerce;

final class NullAgenticCommerceBridge implements AgenticCommerceBridgeInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function resolve(string $baseUrl, mixed $salesChannelContext): AgenticCommerceResourceState
    {
        return new AgenticCommerceResourceState(ucpActive: false);
    }
}
