<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Config;

final class InMemoryArdConfigProvider implements ArdConfigProviderInterface
{
    public function __construct(private readonly ArdConfig $config)
    {
    }

    public function getConfig(mixed $salesChannelContext): ArdConfig
    {
        return $this->config;
    }
}
