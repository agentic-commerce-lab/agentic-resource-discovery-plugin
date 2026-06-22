<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

interface CatalogEntryProviderInterface
{
    /**
     * @return list<CatalogEntry>
     */
    public function getEntries(string $baseUrl, mixed $salesChannelContext): array;
}
