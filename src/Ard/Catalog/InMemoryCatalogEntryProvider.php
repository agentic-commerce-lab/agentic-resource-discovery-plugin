<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

final class InMemoryCatalogEntryProvider implements CatalogEntryProviderInterface
{
    /**
     * @param list<CatalogEntry> $entries
     */
    public function __construct(private readonly array $entries)
    {
    }

    /**
     * @return list<CatalogEntry>
     */
    public function getEntries(string $baseUrl, mixed $salesChannelContext): array
    {
        return $this->entries;
    }
}
