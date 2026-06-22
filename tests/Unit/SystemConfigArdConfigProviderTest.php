<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Config\SystemConfigArdConfigProvider;

return [
    'system config provider reads sales channel scoped plugin settings' => static function (): void {
        $systemConfig = new class {
            /** @var array<string, mixed> */
            private array $values = [
                'sales-channel-1:SwagAgenticResourceDiscovery.config.enabled' => false,
                'sales-channel-1:SwagAgenticResourceDiscovery.config.hostDisplayName' => 'Configured Shop',
                'sales-channel-1:SwagAgenticResourceDiscovery.config.documentationUrl' => 'https://example.com/ard',
                'sales-channel-1:SwagAgenticResourceDiscovery.config.staticEntriesJson' => '[{"identifier":"urn:air:example.com:shopware:custom","displayName":"Custom","type":"application/json","url":"https://example.com/custom.json"}]',
                'sales-channel-1:SwagAgenticResourceDiscovery.config.referralsJson' => '[{"url":"https://registry.example.com/ard","displayName":"Partner Registry"}]',
            ];

            public function get(string $key, ?string $salesChannelId = null): mixed
            {
                return $this->values[($salesChannelId ?? 'global').':'.$key] ?? null;
            }
        };

        $context = new class {
            public function getSalesChannelId(): string
            {
                return 'sales-channel-1';
            }
        };

        $config = (new SystemConfigArdConfigProvider($systemConfig))->getConfig($context);

        assert_same(false, $config->enabled);
        assert_same('Configured Shop', $config->hostDisplayName);
        assert_same('https://example.com/ard', $config->documentationUrl);
        assert_same('[{"identifier":"urn:air:example.com:shopware:custom","displayName":"Custom","type":"application/json","url":"https://example.com/custom.json"}]', $config->staticEntriesJson);
        assert_same([['url' => 'https://registry.example.com/ard', 'displayName' => 'Partner Registry']], $config->referrals);
    },

    'system config provider falls back to global settings and defaults' => static function (): void {
        $systemConfig = new class {
            /** @var array<string, mixed> */
            private array $values = [
                'global:SwagAgenticResourceDiscovery.config.hostDisplayName' => 'Global Shop',
                'global:SwagAgenticResourceDiscovery.config.referralsJson' => '{"referrals":[{"url":"https://global.example.com/ard"}]}',
            ];

            public function get(string $key, ?string $salesChannelId = null): mixed
            {
                return $this->values[($salesChannelId ?? 'global').':'.$key] ?? null;
            }
        };

        $context = new class {
            public function getSalesChannelId(): string
            {
                return 'sales-channel-without-overrides';
            }
        };

        $config = (new SystemConfigArdConfigProvider($systemConfig))->getConfig($context);

        assert_same(true, $config->enabled);
        assert_same('Global Shop', $config->hostDisplayName);
        assert_same(null, $config->documentationUrl);
        assert_same(null, $config->staticEntriesJson);
        assert_same([['url' => 'https://global.example.com/ard']], $config->referrals);
    },

    'system config provider ignores invalid referral json' => static function (): void {
        $systemConfig = new class {
            public function get(string $key, ?string $salesChannelId = null): mixed
            {
                return 'SwagAgenticResourceDiscovery.config.referralsJson' === $key ? 'not json' : null;
            }
        };

        $config = (new SystemConfigArdConfigProvider($systemConfig))->getConfig(null);

        assert_same([], $config->referrals);
    },
];
