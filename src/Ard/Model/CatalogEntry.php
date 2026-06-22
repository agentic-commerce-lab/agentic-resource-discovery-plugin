<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Model;

final class CatalogEntry
{
    /**
     * @param list<string> $tags
     * @param list<string> $capabilities
     * @param list<string> $representativeQueries
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $trustManifest
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $displayName,
        private readonly string $type,
        private readonly ?string $url = null,
        private readonly ?array $data = null,
        private readonly ?string $description = null,
        private readonly array $tags = [],
        private readonly array $capabilities = [],
        private readonly array $representativeQueries = [],
        private readonly ?string $version = null,
        private readonly ?string $updatedAt = null,
        private readonly array $metadata = [],
        private readonly array $trustManifest = [],
    ) {
        if ((null === $this->url) === (null === $this->data)) {
            throw new \InvalidArgumentException('Catalog entries must contain exactly one of url or data.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $entry = [
            'identifier' => $this->identifier,
            'displayName' => $this->displayName,
            'type' => $this->type,
        ];

        if (null !== $this->url) {
            $entry['url'] = $this->url;
        }

        if (null !== $this->data) {
            $entry['data'] = $this->data;
        }

        foreach ([
            'description' => $this->description,
            'tags' => $this->tags,
            'capabilities' => $this->capabilities,
            'representativeQueries' => $this->representativeQueries,
            'version' => $this->version,
            'updatedAt' => $this->updatedAt,
            'metadata' => $this->metadata,
            'trustManifest' => $this->trustManifest,
        ] as $key => $value) {
            if (null === $value || [] === $value) {
                continue;
            }

            $entry[$key] = $value;
        }

        return $entry;
    }
}
