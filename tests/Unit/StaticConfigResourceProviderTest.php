<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Catalog\AiCatalogBuilder;
use Swag\AgenticResourceDiscovery\Ard\Catalog\StaticConfigResourceProvider;
use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Log\InMemoryStaticEntryWarningLogger;

return [
    'static provider accepts an array of catalog entries' => static function (): void {
        $logger = new InMemoryStaticEntryWarningLogger();
        $provider = new StaticConfigResourceProvider($logger);
        $config = new ArdConfig(
            enabled: true,
            hostDisplayName: 'Demo Shop',
            staticEntriesJson: json_encode([
                [
                    'identifier' => 'urn:air:example.com:shopware:custom-api',
                    'displayName' => 'Custom Product Advice API',
                    'type' => 'application/openapi+json',
                    'url' => 'https://example.com/openapi.json',
                    'description' => 'Product advice API for agents.',
                    'representativeQueries' => ['get product advice', 'find compatible products'],
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        $entries = $provider->getEntries('https://example.com', null, $config);
        $entry = $entries[0]->toArray();

        assert_same(1, \count($entries));
        assert_same('urn:air:example.com:shopware:custom-api', $entry['identifier']);
        assert_same('Custom Product Advice API', $entry['displayName']);
        assert_same('https://example.com/openapi.json', $entry['url']);
        assert_same([], $logger->warnings);
    },

    'static provider accepts manifest shaped config' => static function (): void {
        $logger = new InMemoryStaticEntryWarningLogger();
        $provider = new StaticConfigResourceProvider($logger);
        $config = new ArdConfig(
            enabled: true,
            hostDisplayName: 'Demo Shop',
            staticEntriesJson: json_encode([
                'entries' => [
                    [
                        'identifier' => 'urn:air:example.com:shopware:custom-api',
                        'displayName' => 'Custom Product Advice API',
                        'type' => 'application/openapi+json',
                        'url' => 'https://example.com/openapi.json',
                    ],
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        $entries = $provider->getEntries('https://example.com', null, $config);

        assert_same(1, \count($entries));
        assert_same('urn:air:example.com:shopware:custom-api', $entries[0]->toArray()['identifier']);
    },

    'static provider omits invalid entries and records warnings' => static function (): void {
        $logger = new InMemoryStaticEntryWarningLogger();
        $provider = new StaticConfigResourceProvider($logger);
        $config = new ArdConfig(
            enabled: true,
            hostDisplayName: 'Demo Shop',
            staticEntriesJson: json_encode([
                [
                    'identifier' => 'urn:ai:example.com:shopware:old',
                    'displayName' => 'Old Identifier',
                    'type' => 'application/json',
                    'url' => 'https://example.com/old.json',
                ],
                [
                    'identifier' => 'urn:air:example.com:shopware:both',
                    'displayName' => 'Both Value and Reference',
                    'type' => 'application/json',
                    'url' => 'https://example.com/both.json',
                    'data' => ['ok' => true],
                ],
                [
                    'identifier' => 'urn:air:example.com:shopware:valid',
                    'displayName' => 'Valid Entry',
                    'type' => 'application/json',
                    'data' => ['ok' => true],
                    'representativeQueries' => ['one', 'two'],
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        $entries = $provider->getEntries('https://example.com', null, $config);

        assert_same(1, \count($entries));
        assert_same('urn:air:example.com:shopware:valid', $entries[0]->toArray()['identifier']);
        assert_same(2, \count($logger->warnings));
        assert_same(0, $logger->warnings[0]['index']);
        assert_same(1, $logger->warnings[1]['index']);
    },

    'builder includes valid static config entries in manifest' => static function (): void {
        $builder = new AiCatalogBuilder([
            new StaticConfigResourceProvider(new InMemoryStaticEntryWarningLogger()),
        ]);
        $config = new ArdConfig(
            enabled: true,
            hostDisplayName: 'Demo Shop',
            staticEntriesJson: json_encode([
                [
                    'identifier' => 'urn:air:example.com:shopware:custom-api',
                    'displayName' => 'Custom Product Advice API',
                    'type' => 'application/openapi+json',
                    'url' => 'https://example.com/openapi.json',
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        $manifest = $builder->build('https://example.com', null, $config)->toArray();

        assert_same(1, \count($manifest['entries']));
        assert_same('urn:air:example.com:shopware:custom-api', $manifest['entries'][0]['identifier']);
    },
];
