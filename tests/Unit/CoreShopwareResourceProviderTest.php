<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Catalog\CoreShopwareResourceProvider;

return [
    'core provider emits an ARD registry entry with current urn:air identifier' => static function (): void {
        $provider = new CoreShopwareResourceProvider();

        $entries = $provider->getEntries('https://example.com', null);
        assert_true(1 === \count($entries), 'Expected exactly one core entry.');

        $entry = $entries[0]->toArray();

        assert_same('urn:air:example.com:shopware:ard-registry', $entry['identifier']);
        assert_true(!str_starts_with($entry['identifier'], 'urn:ai:'), 'Identifier must not use deprecated urn:ai prefix.');
        assert_same('application/ai-registry+json', $entry['type']);
        assert_same('https://example.com/ard', $entry['url']);
    },

    'core provider entry uses exactly one value or reference field' => static function (): void {
        $provider = new CoreShopwareResourceProvider();

        $entry = $provider->getEntries('https://example.com/', null)[0]->toArray();

        assert_true(\array_key_exists('url', $entry), 'Expected url reference.');
        assert_true(!\array_key_exists('data', $entry), 'Expected no embedded data when url is present.');
    },

    'service configuration declares the core provider tag' => static function (): void {
        $services = \file_get_contents(__DIR__.'/../../src/Resources/config/services.php');

        assert_true(false !== $services, 'Expected services.php to be readable.');
        assert_true(str_contains($services, 'CoreShopwareResourceProvider::class'), 'Expected core provider service registration.');
        assert_true(str_contains($services, 'swag_agentic_resource_discovery.catalog_entry_provider'), 'Expected catalog provider tag.');
    },
];
