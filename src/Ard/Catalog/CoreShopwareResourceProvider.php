<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

final class CoreShopwareResourceProvider implements CatalogEntryProviderInterface
{
    /**
     * @return list<CatalogEntry>
     */
    public function getEntries(string $baseUrl, mixed $salesChannelContext): array
    {
        $normalizedBaseUrl = rtrim($baseUrl, '/');
        $publisher = (string) parse_url($normalizedBaseUrl, \PHP_URL_HOST);

        if ('' === $publisher) {
            $publisher = 'localhost';
        }

        return [
            new CatalogEntry(
                identifier: sprintf('urn:air:%s:shopware:ard-registry', $publisher),
                displayName: 'Shopware ARD Registry',
                type: 'application/ai-registry+json',
                url: $normalizedBaseUrl.'/ard',
                description: 'ARD registry for this Shopware sales channel.',
                tags: ['shopware', 'commerce', 'registry'],
                capabilities: ['ard.search', 'ard.explore', 'ard.list'],
                representativeQueries: [
                    'find shopware commerce resources',
                    'discover agentic shopping capabilities',
                ],
            ),
        ];
    }
}
