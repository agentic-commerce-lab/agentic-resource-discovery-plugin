<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Log;

final class NullStaticEntryWarningLogger implements StaticEntryWarningLoggerInterface
{
    public function warning(int $index, string $message): void
    {
    }
}
