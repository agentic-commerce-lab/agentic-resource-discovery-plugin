<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Config;

final class SystemConfigArdConfigProvider implements ArdConfigProviderInterface
{
    private const CONFIG_PREFIX = 'SwagAgenticResourceDiscovery.config.';

    public function __construct(private readonly object $systemConfigService)
    {
    }

    public function getConfig(mixed $salesChannelContext): ArdConfig
    {
        $salesChannelId = $this->salesChannelId($salesChannelContext);

        return new ArdConfig(
            $this->boolConfig('enabled', $salesChannelId, true),
            $this->stringConfig('hostDisplayName', $salesChannelId, 'Shopware Agentic Resource Discovery'),
            $this->nullableStringConfig('documentationUrl', $salesChannelId),
            $this->referralsConfig($salesChannelId),
            $this->nullableStringConfig('staticEntriesJson', $salesChannelId),
        );
    }

    private function boolConfig(string $key, ?string $salesChannelId, bool $default): bool
    {
        $value = $this->read($key, $salesChannelId);

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $default,
            };
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return $default;
    }

    private function stringConfig(string $key, ?string $salesChannelId, string $default): string
    {
        $value = $this->read($key, $salesChannelId);

        return \is_string($value) && '' !== trim($value) ? $value : $default;
    }

    private function nullableStringConfig(string $key, ?string $salesChannelId): ?string
    {
        $value = $this->read($key, $salesChannelId);

        return \is_string($value) && '' !== trim($value) ? $value : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function referralsConfig(?string $salesChannelId): array
    {
        $json = $this->nullableStringConfig('referralsJson', $salesChannelId);
        if (null === $json) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!\is_array($decoded)) {
            return [];
        }

        $referrals = \is_array($decoded['referrals'] ?? null) ? $decoded['referrals'] : $decoded;
        if (!array_is_list($referrals)) {
            return [];
        }

        $valid = [];
        foreach ($referrals as $referral) {
            if (\is_array($referral)) {
                $valid[] = $referral;
            }
        }

        return $valid;
    }

    private function read(string $key, ?string $salesChannelId): mixed
    {
        $fullKey = self::CONFIG_PREFIX.$key;

        $value = $this->systemConfigService->get($fullKey, $salesChannelId);
        if (null !== $value || null === $salesChannelId) {
            return $value;
        }

        return $this->systemConfigService->get($fullKey);
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
}
