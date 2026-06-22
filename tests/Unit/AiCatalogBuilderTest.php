<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Catalog\CoreShopwareResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Catalog\InMemoryCatalogEntryProvider;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

return [
    'builder combines host config and provider entries' => static function (): void {
        $builder = new AiCatalogBuilder([
            new CoreShopwareResourceProvider(),
            new InMemoryCatalogEntryProvider([
                new CatalogEntry(
                    identifier: 'urn:air:example.com:shopware:custom',
                    displayName: 'Custom Entry',
                    type: 'application/openapi+json',
                    url: 'https://example.com/openapi.json',
                ),
            ]),
        ]);

        $manifest = $builder->build(
            'https://example.com/',
            null,
            new ArdConfig(
                enabled: true,
                hostDisplayName: 'Demo Shop',
                documentationUrl: 'https://example.com/docs',
            ),
        )->toArray();

        assert_same('Demo Shop', $manifest['host']['displayName']);
        assert_same('https://example.com/docs', $manifest['host']['documentationUrl']);
        assert_same(2, \count($manifest['entries']));
        assert_same('urn:air:example.com:shopware:ard-registry', $manifest['entries'][0]['identifier']);
        assert_same('urn:air:example.com:shopware:custom', $manifest['entries'][1]['identifier']);
    },
];
