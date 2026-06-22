<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Model;

final class AiCatalogManifest
{
    /**
     * @param list<CatalogEntry> $entries
     */
    public function __construct(
        private readonly string $hostDisplayName,
        private readonly array $entries,
        private readonly ?string $documentationUrl = null,
    ) {
    }

    /**
     * @return array{specVersion: string, host: array<string, string>, entries: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        $host = [
            'displayName' => $this->hostDisplayName,
        ];

        if (null !== $this->documentationUrl && '' !== $this->documentationUrl) {
            $host['documentationUrl'] = $this->documentationUrl;
        }

        return [
            'specVersion' => '1.0',
            'host' => $host,
            'entries' => array_map(
                static fn (CatalogEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
        ];
    }
}
