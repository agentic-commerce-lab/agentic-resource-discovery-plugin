<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Model\AiCatalogManifest;
use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

return [
    'manifest emits spec version host and entries' => static function (): void {
        $manifest = new AiCatalogManifest(
            hostDisplayName: 'Demo Shop',
            entries: [
                new CatalogEntry(
                    identifier: 'urn:air:example.com:shopware:ard-registry',
                    displayName: 'Shopware ARD Registry',
                    type: 'application/ai-registry+json',
                    url: 'https://example.com/ard',
                ),
            ],
        );

        $payload = $manifest->toArray();

        assert_same('1.0', $payload['specVersion']);
        assert_same('Demo Shop', $payload['host']['displayName']);
        assert_true(!\array_key_exists('documentationUrl', $payload['host']), 'Empty documentation URL should be omitted.');
        assert_same('urn:air:example.com:shopware:ard-registry', $payload['entries'][0]['identifier']);
    },

    'manifest includes documentation url when configured' => static function (): void {
        $manifest = new AiCatalogManifest(
            hostDisplayName: 'Demo Shop',
            documentationUrl: 'https://example.com/docs',
            entries: [],
        );

        $payload = $manifest->toArray();

        assert_same('https://example.com/docs', $payload['host']['documentationUrl']);
    },
];
