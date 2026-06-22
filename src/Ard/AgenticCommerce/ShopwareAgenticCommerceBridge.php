<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\AgenticCommerce;

final class ShopwareAgenticCommerceBridge implements AgenticCommerceBridgeInterface
{
    public function __construct(
        private readonly ?object $ucpConfigService,
        private readonly ?object $shopwareVersionDetector,
    ) {
    }

    public function isAvailable(): bool
    {
        return null !== $this->ucpConfigService && method_exists($this->ucpConfigService, 'getConfig');
    }

    public function resolve(string $baseUrl, mixed $salesChannelContext): AgenticCommerceResourceState
    {
        if (!$this->isAvailable()) {
            return new AgenticCommerceResourceState(ucpActive: false);
        }

        try {
            $config = $this->ucpConfigService->getConfig($this->salesChannelId($salesChannelContext));
        } catch (\Throwable) {
            return new AgenticCommerceResourceState(ucpActive: false);
        }

        if (!\is_object($config) || true !== ($config->active ?? false)) {
            return new AgenticCommerceResourceState(ucpActive: false);
        }

        $storeApiMcpAvailable = $this->storeApiMcpAvailable();

        return new AgenticCommerceResourceState(
            ucpActive: true,
            mcpAvailable: $this->mcpAvailable($config, $storeApiMcpAvailable),
            nativeDiscoveryAvailable: true,
            enabledCapabilities: $this->enabledCapabilities($config),
            protocolVersion: \is_string($config->ucpVersion ?? null) && '' !== $config->ucpVersion
                ? $config->ucpVersion
                : AgenticCommerceResourceState::UCP_VERSION,
        );
    }

    /**
     * @return list<string>
     */
    private function enabledCapabilities(object $config): array
    {
        if (method_exists($config, 'runtimeEnabledCapabilityDescriptors')) {
            try {
                $capabilities = $config->runtimeEnabledCapabilityDescriptors();
                if (\is_array($capabilities)) {
                    return $this->stringList($capabilities);
                }
            } catch (\Throwable) {
                return [];
            }
        }

        if (\is_array($config->enabledCapabilities ?? null)) {
            return $this->descriptorNamesForConfigKeys($config->enabledCapabilities);
        }

        return [];
    }

    private function mcpAvailable(object $config, bool $storeApiMcpAvailable): bool
    {
        if (!$storeApiMcpAvailable || !method_exists($config, 'runtimeTransports')) {
            return false;
        }

        try {
            $transports = $config->runtimeTransports($storeApiMcpAvailable);
        } catch (\Throwable) {
            return false;
        }

        if (!\is_array($transports)) {
            return false;
        }

        foreach ($transports as $transport) {
            $value = $this->transportValue($transport);
            if ('mcp' === strtolower($value)) {
                return true;
            }
        }

        return false;
    }

    private function storeApiMcpAvailable(): bool
    {
        if (null === $this->shopwareVersionDetector || !method_exists($this->shopwareVersionDetector, 'supportsStoreApiMcp')) {
            return false;
        }

        try {
            return true === $this->shopwareVersionDetector->supportsStoreApiMcp();
        } catch (\Throwable) {
            return false;
        }
    }

    private function salesChannelId(mixed $salesChannelContext): ?string
    {
        if (!\is_object($salesChannelContext) || !method_exists($salesChannelContext, 'getSalesChannelId')) {
            return null;
        }

        try {
            $salesChannelId = $salesChannelContext->getSalesChannelId();
        } catch (\Throwable) {
            return null;
        }

        return \is_string($salesChannelId) && '' !== $salesChannelId ? $salesChannelId : null;
    }

    /**
     * @param array<mixed> $values
     *
     * @return list<string>
     */
    private function stringList(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (\is_string($value) && '' !== $value) {
                $strings[] = $value;
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * @param array<mixed> $configKeys
     *
     * @return list<string>
     */
    private function descriptorNamesForConfigKeys(array $configKeys): array
    {
        $map = [
            'catalog' => AgenticCommerceResourceState::UCP_CAPABILITY_CATALOG,
            'cart' => AgenticCommerceResourceState::UCP_CAPABILITY_CART,
            'discount' => AgenticCommerceResourceState::UCP_CAPABILITY_DISCOUNT,
            'checkout' => AgenticCommerceResourceState::UCP_CAPABILITY_CHECKOUT,
            'order' => AgenticCommerceResourceState::UCP_CAPABILITY_ORDER,
            'identity_linking' => AgenticCommerceResourceState::UCP_CAPABILITY_IDENTITY_LINKING,
            'payment_tokenization' => AgenticCommerceResourceState::UCP_CAPABILITY_PAYMENT_TOKENIZATION,
        ];

        $descriptors = [];
        foreach ($configKeys as $configKey) {
            if (\is_string($configKey) && isset($map[$configKey])) {
                $descriptors[] = $map[$configKey];
            }
        }

        return array_values(array_unique($descriptors));
    }

    private function transportValue(mixed $transport): string
    {
        if (\is_string($transport)) {
            return $transport;
        }

        if ($transport instanceof \BackedEnum) {
            return (string) $transport->value;
        }

        if (\is_object($transport) && \is_string($transport->value ?? null)) {
            return $transport->value;
        }

        return '';
    }
}
