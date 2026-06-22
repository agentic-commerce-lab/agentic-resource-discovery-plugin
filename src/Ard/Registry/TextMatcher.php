<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Registry;

final class TextMatcher
{
    /**
     * @param array<string, mixed> $entry
     */
    public function score(array $entry, string $text): int
    {
        $queryTokens = $this->tokens($text);
        if ([] === $queryTokens) {
            return 100;
        }

        $haystack = implode(' ', array_filter([
            $entry['displayName'] ?? '',
            $entry['description'] ?? '',
            implode(' ', \is_array($entry['tags'] ?? null) ? $entry['tags'] : []),
            implode(' ', \is_array($entry['capabilities'] ?? null) ? $entry['capabilities'] : []),
            implode(' ', \is_array($entry['representativeQueries'] ?? null) ? $entry['representativeQueries'] : []),
        ], static fn (mixed $value): bool => \is_string($value) && '' !== $value));

        $entryTokens = array_flip($this->tokens($haystack));
        $matches = 0;

        foreach ($queryTokens as $token) {
            if (isset($entryTokens[$token])) {
                ++$matches;
            }
        }

        return (int) round(($matches / \count($queryTokens)) * 100);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        preg_match_all('/[a-z0-9]+/i', strtolower($value), $matches);

        return array_values(array_unique($matches[0] ?? []));
    }
}
