<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Registry;

final class FilterMatcher
{
    /**
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $filter
     */
    public function matches(array $entry, array $filter): bool
    {
        foreach ($filter as $path => $expectedValues) {
            $expectedList = \is_array($expectedValues) ? $expectedValues : [$expectedValues];
            $actualValues = $this->values($entry, (string) $path);

            $matched = false;
            foreach ($actualValues as $actualValue) {
                foreach ($expectedList as $expectedValue) {
                    if ((string) $actualValue === (string) $expectedValue) {
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return list<string|int|float|bool>
     */
    public function values(array $entry, string $path): array
    {
        if ('publisher' === $path) {
            $identifier = (string) ($entry['identifier'] ?? '');
            $parts = explode(':', $identifier);

            return isset($parts[2]) && '' !== $parts[2] ? [$parts[2]] : [];
        }

        return $this->collectValues([$entry], explode('.', $path));
    }

    /**
     * @param list<mixed> $items
     * @param list<string> $segments
     *
     * @return list<string|int|float|bool>
     */
    private function collectValues(array $items, array $segments): array
    {
        if ([] === $segments) {
            $values = [];
            foreach ($items as $item) {
                if (\is_array($item)) {
                    foreach ($item as $nestedItem) {
                        if (\is_scalar($nestedItem)) {
                            $values[] = $nestedItem;
                        }
                    }
                    continue;
                }

                if (\is_scalar($item)) {
                    $values[] = $item;
                }
            }

            return $values;
        }

        $segment = array_shift($segments);
        $next = [];

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            if (array_is_list($item)) {
                foreach ($item as $nestedItem) {
                    $next[] = $nestedItem;
                }
                array_unshift($segments, $segment);

                return $this->collectValues($next, $segments);
            }

            if (array_key_exists($segment, $item)) {
                $next[] = $item[$segment];
            }
        }

        return $this->collectValues($next, $segments);
    }
}
