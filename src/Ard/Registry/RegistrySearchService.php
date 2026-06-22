<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Registry;

final class RegistrySearchService
{
    public function __construct(
        private readonly FilterMatcher $filterMatcher,
        private readonly TextMatcher $textMatcher,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $query
     * @param list<array<string, mixed>> $referrals
     *
     * @return array<string, mixed>
     */
    public function search(array $entries, string $source, array $query, int $pageSize, ?string $pageToken, string $federation, array $referrals): array
    {
        $matched = $this->matchedEntries($entries, $query);
        $text = (string) ($query['text'] ?? '');

        $results = [];
        foreach ($matched as $entry) {
            $score = $this->textMatcher->score($entry, $text);
            if ($score <= 0) {
                continue;
            }

            $entry['score'] = $score;
            $entry['source'] = $source;
            $results[] = $entry;
        }

        usort($results, static fn (array $left, array $right): int => ($right['score'] <=> $left['score']) ?: strcmp((string) $left['identifier'], (string) $right['identifier']));

        $response = [
            'results' => $this->page($results, $pageSize, $pageToken, $nextPageToken),
        ];

        if (null !== $nextPageToken) {
            $response['pageToken'] = $nextPageToken;
        }

        if ('referrals' === $federation && [] !== $referrals) {
            $response['referrals'] = $referrals;
        }

        return $response;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $filter
     *
     * @return array{items: list<array<string, mixed>>, total: int, pageToken?: string}
     */
    public function list(array $entries, array $filter, int $pageSize, ?string $pageToken): array
    {
        $matched = array_values(array_filter(
            $entries,
            fn (array $entry): bool => $this->filterMatcher->matches($entry, $filter),
        ));

        $response = [
            'items' => $this->page($matched, $pageSize, $pageToken, $nextPageToken),
            'total' => \count($matched),
        ];

        if (null !== $nextPageToken) {
            $response['pageToken'] = $nextPageToken;
        }

        return $response;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $query
     * @param list<array<string, mixed>> $facets
     *
     * @return array{resultType: string, facets: array<string, array<string, mixed>>}
     */
    public function explore(array $entries, array $query, array $facets): array
    {
        $matched = $this->matchedEntries($entries, $query);
        $facetResults = [];

        foreach ($facets as $facet) {
            if (!\is_array($facet) || !\is_string($facet['field'] ?? null)) {
                continue;
            }

            $field = $facet['field'];
            $limit = isset($facet['limit']) && \is_int($facet['limit']) ? max(1, $facet['limit']) : 20;
            $minCount = isset($facet['minCount']) && \is_int($facet['minCount']) ? max(1, $facet['minCount']) : 1;
            $counts = [];

            foreach ($matched as $entry) {
                foreach ($this->filterMatcher->values($entry, $field) as $value) {
                    $value = (string) $value;
                    $counts[$value] = ($counts[$value] ?? 0) + 1;
                }
            }

            arsort($counts);
            $buckets = [];
            $otherCount = 0;
            foreach ($counts as $value => $count) {
                if ($count < $minCount) {
                    continue;
                }

                if (\count($buckets) >= $limit) {
                    $otherCount += $count;
                    continue;
                }

                $buckets[] = ['value' => $value, 'count' => $count];
            }

            $facetResults[$field] = ['buckets' => $buckets];
            if ($otherCount > 0) {
                $facetResults[$field]['otherCount'] = $otherCount;
            }
        }

        return [
            'resultType' => 'facets',
            'facets' => $facetResults,
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $query
     *
     * @return list<array<string, mixed>>
     */
    private function matchedEntries(array $entries, array $query): array
    {
        $filter = \is_array($query['filter'] ?? null) ? $query['filter'] : [];

        return array_values(array_filter(
            $entries,
            fn (array $entry): bool => $this->filterMatcher->matches($entry, $filter),
        ));
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    private function page(array $items, int $pageSize, ?string $pageToken, ?string &$nextPageToken): array
    {
        $offset = $this->offset($pageToken);
        $page = \array_slice($items, $offset, $pageSize);
        $nextOffset = $offset + $pageSize;
        $nextPageToken = $nextOffset < \count($items)
            ? base64_encode(json_encode(['offset' => $nextOffset], \JSON_THROW_ON_ERROR))
            : null;

        return $page;
    }

    private function offset(?string $pageToken): int
    {
        if (null === $pageToken || '' === $pageToken) {
            return 0;
        }

        try {
            $decoded = json_decode((string) base64_decode($pageToken, true), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return 0;
        }

        return isset($decoded['offset']) && \is_int($decoded['offset']) && $decoded['offset'] > 0 ? $decoded['offset'] : 0;
    }
}
