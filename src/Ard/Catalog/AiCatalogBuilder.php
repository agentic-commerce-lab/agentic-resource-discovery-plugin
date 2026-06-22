<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Model\AiCatalogManifest;

final class AiCatalogBuilder
{
    /**
     * @param iterable<CatalogEntryProviderInterface> $entryProviders
     */
    public function __construct(private readonly iterable $entryProviders)
    {
    }

    public function build(string $baseUrl, mixed $salesChannelContext, ArdConfig $config): AiCatalogManifest
    {
        $entries = [];

        foreach ($this->entryProviders as $provider) {
            foreach ($provider->getEntries($baseUrl, $salesChannelContext) as $entry) {
                $entries[] = $entry;
            }
        }

        return new AiCatalogManifest(
            hostDisplayName: $config->hostDisplayName,
            entries: $entries,
            documentationUrl: $config->documentationUrl,
        );
    }
}
