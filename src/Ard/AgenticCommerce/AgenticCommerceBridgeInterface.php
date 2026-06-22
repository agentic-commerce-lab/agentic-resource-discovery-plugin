<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\AgenticCommerce;

interface AgenticCommerceBridgeInterface
{
    public function isAvailable(): bool;

    public function resolve(string $baseUrl, mixed $salesChannelContext): AgenticCommerceResourceState;
}
