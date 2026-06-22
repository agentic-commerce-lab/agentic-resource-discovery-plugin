<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Api\RegistryController;
use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Catalog\InMemoryCatalogEntryProvider;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Config\InMemoryArdConfigProvider;
use Swag\AgenticResourceDiscovery\Ard\Http\ArdRequestValidator;
use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;
use Swag\AgenticResourceDiscovery\Ard\Registry\FilterMatcher;
use Swag\AgenticResourceDiscovery\Ard\Registry\RegistrySearchService;
use Swag\AgenticResourceDiscovery\Ard\Registry\TextMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$controller = static function (bool $enabled = true): RegistryController {
    return new RegistryController(
        new AiCatalogBuilder([
            new InMemoryCatalogEntryProvider([
                new CatalogEntry(
                    identifier: 'urn:air:example.com:shopware:catalog',
                    displayName: 'Shopware Catalog Search',
                    type: 'application/mcp-server-card+json',
                    url: 'https://example.com/ucp/mcp',
                    description: 'Search products.',
                    tags: ['shopware', 'commerce'],
                    capabilities: ['catalog.search'],
                    representativeQueries: ['search products', 'find products'],
                    metadata: ['protocol' => 'mcp'],
                ),
            ]),
        ]),
        new InMemoryArdConfigProvider(new ArdConfig($enabled, 'Demo Shop')),
        new RegistrySearchService(new FilterMatcher(), new TextMatcher()),
        new ArdRequestValidator(),
    );
};

return [
    'search endpoint returns spec shaped results' => static function () use ($controller): void {
        $response = $controller()->search(Request::create(
            '/ard/search',
            'POST',
            [],
            [],
            [],
            ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on'],
            '{"query":{"text":"product search"},"pageSize":10}'
        ));
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        assert_same(Response::HTTP_OK, $response->getStatusCode());
        assert_same('urn:air:example.com:shopware:catalog', $payload['results'][0]['identifier']);
        assert_true(isset($payload['results'][0]['score']), 'Expected score.');
        assert_same('https://example.com/ard', $payload['results'][0]['source']);
    },

    'search endpoint returns ard error envelope for invalid request' => static function () use ($controller): void {
        $response = $controller()->search(Request::create('/ard/search', 'POST', [], [], [], [], '{"query":{}}'));
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        assert_same(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        assert_same('INVALID_ARGUMENT', $payload['errorCode']);
    },

    'list endpoint returns filtered items' => static function () use ($controller): void {
        $response = $controller()->agents(Request::create('/ard/agents?filter=metadata.protocol=mcp', 'GET', [], [], [], ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']));
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        assert_same(Response::HTTP_OK, $response->getStatusCode());
        assert_same(1, $payload['total']);
        assert_same('urn:air:example.com:shopware:catalog', $payload['items'][0]['identifier']);
    },

    'explore endpoint returns facet response' => static function () use ($controller): void {
        $response = $controller()->explore(Request::create(
            '/ard/explore',
            'POST',
            [],
            [],
            [],
            ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on'],
            '{"resultType":{"facets":[{"field":"type"}]}}'
        ));
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        assert_same(Response::HTTP_OK, $response->getStatusCode());
        assert_same('facets', $payload['resultType']);
        assert_same('application/mcp-server-card+json', $payload['facets']['type']['buckets'][0]['value']);
    },

    'disabled registry endpoints return not found' => static function () use ($controller): void {
        $response = $controller(false)->search(Request::create('/ard/search', 'POST', [], [], [], [], '{"query":{"text":"shop"}}'));

        assert_same(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    },
];
