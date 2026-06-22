<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\AgenticCommerce\ShopwareAgenticCommerceBridge;

return [
    'shopware agentic commerce bridge is unavailable without config service' => static function (): void {
        $bridge = new ShopwareAgenticCommerceBridge(null, null);

        assert_same(false, $bridge->isAvailable());
        assert_same(false, $bridge->resolve('https://example.com', null)->ucpActive);
    },

    'shopware agentic commerce bridge reads active ucp config for sales channel' => static function (): void {
        $configService = new class {
            public ?string $requestedSalesChannelId = null;

            public function getConfig(?string $salesChannelId = null): object
            {
                $this->requestedSalesChannelId = $salesChannelId;

                return new class {
                    public bool $active = true;
                    public string $ucpVersion = '2026-04-08';

                    public function runtimeEnabledCapabilityDescriptors(): array
                    {
                        return [
                            'dev.ucp.shopping.catalog',
                            'dev.ucp.shopping.checkout',
                        ];
                    }

                    public function runtimeTransports(bool $storeApiMcpAvailable = false): array
                    {
                        return ['rest'];
                    }
                };
            }
        };

        $context = new class {
            public function getSalesChannelId(): string
            {
                return 'sales-channel-1';
            }
        };

        $bridge = new ShopwareAgenticCommerceBridge($configService, null);
        $state = $bridge->resolve('https://example.com', $context);

        assert_same(true, $bridge->isAvailable());
        assert_same('sales-channel-1', $configService->requestedSalesChannelId);
        assert_same(true, $state->ucpActive);
        assert_same(false, $state->mcpAvailable);
        assert_same(true, $state->nativeDiscoveryAvailable);
        assert_same(['dev.ucp.shopping.catalog', 'dev.ucp.shopping.checkout'], $state->enabledCapabilities);
        assert_same('2026-04-08', $state->protocolVersion);
    },

    'shopware agentic commerce bridge advertises mcp when config and runtime support it' => static function (): void {
        $configService = new class {
            public function getConfig(?string $salesChannelId = null): object
            {
                return new class {
                    public bool $active = true;
                    public string $ucpVersion = '2026-04-08';

                    public function runtimeEnabledCapabilityDescriptors(): array
                    {
                        return ['dev.ucp.shopping.catalog'];
                    }

                    public function runtimeTransports(bool $storeApiMcpAvailable = false): array
                    {
                        return $storeApiMcpAvailable ? ['rest', 'mcp'] : ['rest'];
                    }
                };
            }
        };
        $versionDetector = new class {
            public function supportsStoreApiMcp(): bool
            {
                return true;
            }
        };

        $state = (new ShopwareAgenticCommerceBridge($configService, $versionDetector))->resolve('https://example.com', null);

        assert_same(true, $state->ucpActive);
        assert_same(true, $state->mcpAvailable);
    },

    'shopware agentic commerce bridge degrades to inactive on companion errors' => static function (): void {
        $configService = new class {
            public function getConfig(?string $salesChannelId = null): object
            {
                throw new RuntimeException('Companion plugin config failed.');
            }
        };

        $state = (new ShopwareAgenticCommerceBridge($configService, null))->resolve('https://example.com', null);

        assert_same(false, $state->ucpActive);
        assert_same(false, $state->mcpAvailable);
        assert_same(false, $state->nativeDiscoveryAvailable);
    },
];
