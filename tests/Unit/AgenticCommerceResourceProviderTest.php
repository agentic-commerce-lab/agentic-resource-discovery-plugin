<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\AgenticCommerceResourceState;
use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\InMemoryAgenticCommerceBridge;
use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\NullAgenticCommerceBridge;
use Swag\AgenticResourceDiscovery\Ard\Catalog\AgenticCommerceResourceProvider;

return [
    'agentic commerce absent produces no entries' => static function (): void {
        $provider = new AgenticCommerceResourceProvider(new NullAgenticCommerceBridge());

        $entries = $provider->getEntries('https://example.com', null);

        assert_same([], $entries);
    },

    'ucp inactive produces no ucp or mcp entries' => static function (): void {
        $provider = new AgenticCommerceResourceProvider(new InMemoryAgenticCommerceBridge(
            available: true,
            state: new AgenticCommerceResourceState(ucpActive: false),
        ));

        $entries = $provider->getEntries('https://example.com', null);

        assert_same([], $entries);
    },

    'ucp active without mcp produces profile entry only' => static function (): void {
        $provider = new AgenticCommerceResourceProvider(new InMemoryAgenticCommerceBridge(
            available: true,
            state: new AgenticCommerceResourceState(
                ucpActive: true,
                enabledCapabilities: [
                    'dev.ucp.shopping.catalog',
                    'dev.ucp.shopping.cart',
                    'dev.ucp.shopping.checkout',
                ],
            ),
        ));

        $entries = array_map(static fn ($entry): array => $entry->toArray(), $provider->getEntries('https://example.com', null));

        assert_same(1, \count($entries));
        assert_same('urn:air:example.com:shopware:ucp-profile', $entries[0]['identifier']);
        assert_same('application/json', $entries[0]['type']);
        assert_same('https://example.com/.well-known/ucp', $entries[0]['url']);
        assert_same(['dev.ucp.shopping.catalog', 'dev.ucp.shopping.cart', 'dev.ucp.shopping.checkout'], $entries[0]['capabilities']);
        assert_same('ucp', $entries[0]['metadata']['protocol']);
        assert_same('2026-04-08', $entries[0]['metadata']['protocolVersion']);
    },

    'ucp active with mcp produces profile and mcp entries' => static function (): void {
        $provider = new AgenticCommerceResourceProvider(new InMemoryAgenticCommerceBridge(
            available: true,
            state: new AgenticCommerceResourceState(
                ucpActive: true,
                mcpAvailable: true,
                enabledCapabilities: ['dev.ucp.shopping.catalog', 'dev.ucp.shopping.order'],
            ),
        ));

        $entries = array_map(static fn ($entry): array => $entry->toArray(), $provider->getEntries('https://example.com', null));

        assert_same(2, \count($entries));
        assert_same('urn:air:example.com:shopware:ucp-profile', $entries[0]['identifier']);
        assert_same('urn:air:example.com:shopware:ucp-mcp', $entries[1]['identifier']);
        assert_same('application/mcp-server-card+json', $entries[1]['type']);
        assert_same('https://example.com/ucp/mcp', $entries[1]['url']);
        assert_true(\in_array('catalog.search', $entries[1]['capabilities'], true), 'Expected MCP catalog search capability.');
        assert_true(\in_array('order.get', $entries[1]['capabilities'], true), 'Expected MCP order capability.');
    },

    'native discovery routes produce document entries when active and available' => static function (): void {
        $provider = new AgenticCommerceResourceProvider(new InMemoryAgenticCommerceBridge(
            available: true,
            state: new AgenticCommerceResourceState(
                ucpActive: true,
                nativeDiscoveryAvailable: true,
            ),
        ));

        $entries = array_map(static fn ($entry): array => $entry->toArray(), $provider->getEntries('https://example.com', null));
        $identifiers = array_column($entries, 'identifier');

        assert_true(\in_array('urn:air:example.com:shopware:agents-md', $identifiers, true), 'Expected agents.md entry.');
        assert_true(\in_array('urn:air:example.com:shopware:llms-txt', $identifiers, true), 'Expected llms.txt entry.');
    },
];
