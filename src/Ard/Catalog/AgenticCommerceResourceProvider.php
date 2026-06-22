<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\AgenticCommerceBridgeInterface;
use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\AgenticCommerceResourceState;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

final class AgenticCommerceResourceProvider implements CatalogEntryProviderInterface
{
    public function __construct(private readonly AgenticCommerceBridgeInterface $bridge)
    {
    }

    /**
     * @return list<CatalogEntry>
     */
    public function getEntries(string $baseUrl, mixed $salesChannelContext, ?ArdConfig $config = null): array
    {
        if (!$this->bridge->isAvailable()) {
            return [];
        }

        $state = $this->bridge->resolve($baseUrl, $salesChannelContext);
        if (!$state->ucpActive) {
            return [];
        }

        $normalizedBaseUrl = rtrim($baseUrl, '/');
        $publisher = $this->publisher($normalizedBaseUrl);
        $entries = [$this->ucpProfileEntry($publisher, $normalizedBaseUrl, $state)];

        if ($state->mcpAvailable) {
            $entries[] = $this->mcpEntry($publisher, $normalizedBaseUrl, $state);
        }

        if ($state->nativeDiscoveryAvailable) {
            $entries[] = new CatalogEntry(
                identifier: sprintf('urn:air:%s:shopware:agents-md', $publisher),
                displayName: 'Shopware Agentic Discovery Guide',
                type: 'text/markdown',
                url: $normalizedBaseUrl.'/agents.md',
                description: 'Agent-facing operating guide for this Shopware storefront.',
                tags: ['shopware', 'agentic-commerce', 'discovery'],
                capabilities: ['agentic.discovery'],
                representativeQueries: [
                    'read agent instructions for this shop',
                    'find storefront rules for agents',
                ],
                metadata: ['shopware.agenticCommerce' => true],
            );
            $entries[] = new CatalogEntry(
                identifier: sprintf('urn:air:%s:shopware:llms-txt', $publisher),
                displayName: 'Shopware LLM Instructions',
                type: 'text/plain',
                url: $normalizedBaseUrl.'/llms.txt',
                description: 'LLM-facing discovery instructions for this Shopware storefront.',
                tags: ['shopware', 'agentic-commerce', 'discovery'],
                capabilities: ['agentic.discovery'],
                representativeQueries: [
                    'read llm instructions for this shop',
                    'find ai crawler guidance for this storefront',
                ],
                metadata: ['shopware.agenticCommerce' => true],
            );
        }

        return $entries;
    }

    private function ucpProfileEntry(string $publisher, string $baseUrl, AgenticCommerceResourceState $state): CatalogEntry
    {
        return new CatalogEntry(
            identifier: sprintf('urn:air:%s:shopware:ucp-profile', $publisher),
            displayName: 'Shopware UCP Profile',
            type: 'application/json',
            url: $baseUrl.'/.well-known/ucp',
            description: 'Universal Commerce Protocol profile for this Shopware sales channel.',
            tags: ['shopware', 'ucp', 'commerce'],
            capabilities: $state->enabledCapabilities,
            representativeQueries: [
                'discover shopping protocol capabilities',
                'find checkout and cart operations for this shop',
            ],
            metadata: [
                'shopware.agenticCommerce' => true,
                'protocol' => 'ucp',
                'protocolVersion' => $state->protocolVersion,
            ],
        );
    }

    private function mcpEntry(string $publisher, string $baseUrl, AgenticCommerceResourceState $state): CatalogEntry
    {
        return new CatalogEntry(
            identifier: sprintf('urn:air:%s:shopware:ucp-mcp', $publisher),
            displayName: 'Shopware UCP MCP',
            type: 'application/mcp-server-card+json',
            url: $baseUrl.'/ucp/mcp',
            description: 'MCP transport for Shopware UCP shopping operations.',
            tags: ['shopware', 'ucp', 'mcp', 'commerce'],
            capabilities: $this->mcpCapabilities($state->enabledCapabilities),
            representativeQueries: [
                'search products in this shop',
                'create a cart and checkout through shopware',
            ],
            metadata: [
                'shopware.agenticCommerce' => true,
                'protocol' => 'mcp',
                'protocolVersion' => $state->protocolVersion,
            ],
        );
    }

    /**
     * @param list<string> $ucpCapabilities
     *
     * @return list<string>
     */
    private function mcpCapabilities(array $ucpCapabilities): array
    {
        $mapped = [];
        $map = [
            AgenticCommerceResourceState::UCP_CAPABILITY_CATALOG => ['catalog.search', 'catalog.lookup'],
            AgenticCommerceResourceState::UCP_CAPABILITY_CART => ['cart.create', 'cart.get', 'cart.update', 'cart.cancel'],
            AgenticCommerceResourceState::UCP_CAPABILITY_DISCOUNT => ['discount.apply'],
            AgenticCommerceResourceState::UCP_CAPABILITY_CHECKOUT => ['checkout.create', 'checkout.get', 'checkout.update', 'checkout.complete', 'checkout.cancel'],
            AgenticCommerceResourceState::UCP_CAPABILITY_ORDER => ['order.get'],
        ];

        foreach ($ucpCapabilities as $ucpCapability) {
            foreach ($map[$ucpCapability] ?? [] as $mcpCapability) {
                $mapped[] = $mcpCapability;
            }
        }

        return array_values(array_unique($mapped));
    }

    private function publisher(string $baseUrl): string
    {
        $publisher = (string) parse_url($baseUrl, \PHP_URL_HOST);

        return '' !== $publisher ? $publisher : 'localhost';
    }
}
