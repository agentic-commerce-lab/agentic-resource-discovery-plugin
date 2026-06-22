<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Catalog;

use Swag\AgenticResourceDiscovery\Ard\Config\ArdConfig;
use Swag\AgenticResourceDiscovery\Ard\Log\StaticEntryWarningLoggerInterface;
use Swag\AgenticResourceDiscovery\Ard\Model\CatalogEntry;

final class StaticConfigResourceProvider implements CatalogEntryProviderInterface
{
    private const IDENTIFIER_PATTERN = '/^urn:air:[a-zA-Z0-9.-]+(:[a-zA-Z0-9._-]+)+$/';

    public function __construct(private readonly StaticEntryWarningLoggerInterface $warningLogger)
    {
    }

    /**
     * @return list<CatalogEntry>
     */
    public function getEntries(string $baseUrl, mixed $salesChannelContext, ?ArdConfig $config = null): array
    {
        if (null === $config || null === $config->staticEntriesJson || '' === trim($config->staticEntriesJson)) {
            return [];
        }

        try {
            $decoded = json_decode($config->staticEntriesJson, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->warningLogger->warning(-1, 'Static entries JSON is malformed: '.$exception->getMessage());

            return [];
        }

        $entryPayloads = $this->entryPayloads($decoded);
        $entries = [];

        foreach ($entryPayloads as $index => $entryPayload) {
            if (!\is_array($entryPayload)) {
                $this->warningLogger->warning($index, 'Static entry must be an object.');
                continue;
            }

            $error = $this->validate($entryPayload);
            if (null !== $error) {
                $this->warningLogger->warning($index, $error);
                continue;
            }

            $entries[] = $this->createEntry($entryPayload);
        }

        return $entries;
    }

    /**
     * @return list<mixed>
     */
    private function entryPayloads(mixed $decoded): array
    {
        if (!\is_array($decoded)) {
            return [];
        }

        if (array_is_list($decoded)) {
            return $decoded;
        }

        $entries = $decoded['entries'] ?? [];

        return \is_array($entries) && array_is_list($entries) ? $entries : [];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function validate(array $entry): ?string
    {
        foreach (['identifier', 'displayName', 'type'] as $requiredField) {
            if (!isset($entry[$requiredField]) || !\is_string($entry[$requiredField]) || '' === trim($entry[$requiredField])) {
                return sprintf('Static entry requires non-empty string field "%s".', $requiredField);
            }
        }

        if (1 !== preg_match(self::IDENTIFIER_PATTERN, $entry['identifier'])) {
            return 'Static entry identifier must match urn:air:<publisher>:<namespace>:<agent-name>.';
        }

        $hasUrl = isset($entry['url']);
        $hasData = isset($entry['data']);
        if ($hasUrl === $hasData) {
            return 'Static entry must contain exactly one of url or data.';
        }

        if ($hasUrl && (!\is_string($entry['url']) || false === filter_var($entry['url'], \FILTER_VALIDATE_URL))) {
            return 'Static entry url must be an absolute URL.';
        }

        if ($hasData && !\is_array($entry['data'])) {
            return 'Static entry data must be an object.';
        }

        if (isset($entry['representativeQueries'])) {
            if (!\is_array($entry['representativeQueries']) || !array_is_list($entry['representativeQueries'])) {
                return 'Static entry representativeQueries must be a list.';
            }

            $count = \count($entry['representativeQueries']);
            if ($count < 2 || $count > 5) {
                return 'Static entry representativeQueries must contain 2 to 5 strings.';
            }

            foreach ($entry['representativeQueries'] as $query) {
                if (!\is_string($query) || '' === trim($query)) {
                    return 'Static entry representativeQueries must contain non-empty strings.';
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function createEntry(array $entry): CatalogEntry
    {
        return new CatalogEntry(
            identifier: $entry['identifier'],
            displayName: $entry['displayName'],
            type: $entry['type'],
            url: isset($entry['url']) ? $entry['url'] : null,
            data: isset($entry['data']) && \is_array($entry['data']) ? $entry['data'] : null,
            description: isset($entry['description']) && \is_string($entry['description']) ? $entry['description'] : null,
            tags: $this->stringList($entry['tags'] ?? []),
            capabilities: $this->stringList($entry['capabilities'] ?? []),
            representativeQueries: $this->stringList($entry['representativeQueries'] ?? []),
            version: isset($entry['version']) && \is_string($entry['version']) ? $entry['version'] : null,
            updatedAt: isset($entry['updatedAt']) && \is_string($entry['updatedAt']) ? $entry['updatedAt'] : null,
            metadata: \is_array($entry['metadata'] ?? null) ? $entry['metadata'] : [],
            trustManifest: \is_array($entry['trustManifest'] ?? null) ? $entry['trustManifest'] : [],
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => \is_string($item)));
    }
}
