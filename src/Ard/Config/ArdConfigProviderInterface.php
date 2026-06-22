<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Config;

interface ArdConfigProviderInterface
{
    public function getConfig(mixed $salesChannelContext): ArdConfig;
}
