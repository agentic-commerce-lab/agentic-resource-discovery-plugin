<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Registry\FilterMatcher;
use Swag\AgenticResourceDiscovery\Ard\Registry\RegistrySearchService;
use Swag\AgenticResourceDiscovery\Ard\Registry\TextMatcher;

$entries = static fn (): array => [
    [
        'identifier' => 'urn:air:example.com:shopware:catalog',
        'displayName' => 'Shopware Catalog Search',
        'type' => 'application/mcp-server-card+json',
        'url' => 'https://example.com/ucp/mcp',
        'description' => 'Search products and lookup product details.',
        'tags' => ['shopware', 'commerce'],
        'capabilities' => ['catalog.search', 'catalog.lookup'],
        'representativeQueries' => ['search products', 'find product details'],
        'metadata' => ['protocol' => 'mcp'],
        'trustManifest' => [
            'identity' => 'https://example.com',
            'attestations' => [
                ['type' => 'SOC2-Type2', 'uri' => 'https://example.com/soc2', 'mediaType' => 'text/html'],
            ],
        ],
    ],
    [
        'identifier' => 'urn:air:example.com:shopware:checkout',
        'displayName' => 'Shopware Checkout',
        'type' => 'application/json',
        'url' => 'https://example.com/.well-known/ucp',
        'description' => 'Create carts and complete checkout.',
        'tags' => ['shopware', 'checkout'],
        'capabilities' => ['cart.create', 'checkout.complete'],
        'representativeQueries' => ['create cart', 'complete checkout'],
        'metadata' => ['protocol' => 'ucp'],
    ],
];

return [
    'filter matcher supports scalar arrays nested arrays and publisher' => static function () use ($entries): void {
        $matcher = new FilterMatcher();
        $entry = $entries()[0];

        assert_true($matcher->matches($entry, ['type' => ['application/mcp-server-card+json']]), 'Expected scalar type filter to match.');
        assert_true($matcher->matches($entry, ['tags' => ['commerce']]), 'Expected array tag filter to match.');
        assert_true($matcher->matches($entry, ['metadata.protocol' => ['mcp']]), 'Expected metadata filter to match.');
        assert_true($matcher->matches($entry, ['trustManifest.attestations.type' => ['SOC2-Type2']]), 'Expected nested attestation filter to match.');
        assert_true($matcher->matches($entry, ['publisher' => ['example.com']]), 'Expected derived publisher filter to match.');
        assert_true(!$matcher->matches($entry, ['tags' => ['checkout']]), 'Expected nonmatching filter to fail.');
    },

    'search ranks text matches and adds score and source' => static function () use ($entries): void {
        $service = new RegistrySearchService(new FilterMatcher(), new TextMatcher());

        $response = $service->search(
            entries: $entries(),
            source: 'https://example.com/ard',
            query: ['text' => 'product search', 'filter' => ['type' => ['application/mcp-server-card+json']]],
            pageSize: 10,
            pageToken: null,
            federation: 'none',
            referrals: [],
        );

        assert_same(1, \count($response['results']));
        assert_same('urn:air:example.com:shopware:catalog', $response['results'][0]['identifier']);
        assert_true($response['results'][0]['score'] > 0, 'Expected positive search score.');
        assert_same('https://example.com/ard', $response['results'][0]['source']);
    },

    'search paginates with base64 offset token' => static function () use ($entries): void {
        $service = new RegistrySearchService(new FilterMatcher(), new TextMatcher());

        $firstPage = $service->search($entries(), 'https://example.com/ard', ['text' => 'shopware'], 1, null, 'none', []);
        $secondPage = $service->search($entries(), 'https://example.com/ard', ['text' => 'shopware'], 1, $firstPage['pageToken'], 'none', []);

        assert_same(1, \count($firstPage['results']));
        assert_true(isset($firstPage['pageToken']), 'Expected first page token.');
        assert_same(1, \count($secondPage['results']));
        assert_true($firstPage['results'][0]['identifier'] !== $secondPage['results'][0]['identifier'], 'Expected second page to advance.');
    },

    'list applies filter and returns total' => static function () use ($entries): void {
        $service = new RegistrySearchService(new FilterMatcher(), new TextMatcher());

        $response = $service->list($entries(), ['tags' => ['checkout']], 20, null);

        assert_same(1, $response['total']);
        assert_same('urn:air:example.com:shopware:checkout', $response['items'][0]['identifier']);
    },

    'explore returns facet buckets and other count' => static function () use ($entries): void {
        $service = new RegistrySearchService(new FilterMatcher(), new TextMatcher());

        $response = $service->explore(
            entries: $entries(),
            query: ['filter' => ['tags' => ['shopware']]],
            facets: [['field' => 'tags', 'limit' => 1]],
        );

        assert_same('facets', $response['resultType']);
        assert_same('shopware', $response['facets']['tags']['buckets'][0]['value']);
        assert_same(2, $response['facets']['tags']['buckets'][0]['count']);
        assert_true(isset($response['facets']['tags']['otherCount']), 'Expected otherCount when buckets exceed limit.');
    },
];
