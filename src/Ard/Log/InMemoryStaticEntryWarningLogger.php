<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Log;

final class InMemoryStaticEntryWarningLogger implements StaticEntryWarningLoggerInterface
{
    /**
     * @var list<array{index: int, message: string}>
     */
    public array $warnings = [];

    public function warning(int $index, string $message): void
    {
        $this->warnings[] = [
            'index' => $index,
            'message' => $message,
        ];
    }
}
