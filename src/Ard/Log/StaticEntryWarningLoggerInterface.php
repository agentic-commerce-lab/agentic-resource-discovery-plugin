<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Log;

interface StaticEntryWarningLoggerInterface
{
    public function warning(int $index, string $message): void;
}
