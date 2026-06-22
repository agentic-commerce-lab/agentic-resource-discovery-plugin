<?php

declare(strict_types=1);

namespace Swag\AgenticResourceDiscovery\Ard\Http;

use Symfony\Component\HttpFoundation\Request;

final class ArdRequestValidator
{
    public function validateSearch(Request $request): ValidationResult
    {
        $payload = $this->decodeJson($request);
        if (!$payload->valid) {
            return $payload;
        }

        $data = $payload->payload;
        $query = $data['query'] ?? null;

        if (!\is_array($query)) {
            return ValidationResult::invalid('Search query is required.');
        }

        if (!isset($query['text']) || !\is_string($query['text']) || '' === trim($query['text'])) {
            return ValidationResult::invalid('Search query.text is required.');
        }

        $federation = $data['federation'] ?? 'auto';
        if (!\is_string($federation) || !\in_array($federation, ['auto', 'referrals', 'none'], true)) {
            return ValidationResult::invalid('Unsupported federation value.');
        }

        $data['federation'] = $federation;
        $data['pageSize'] = $this->pageSize($data['pageSize'] ?? 10, 10);
        $data['pageToken'] = isset($data['pageToken']) && \is_string($data['pageToken']) ? $data['pageToken'] : null;

        return ValidationResult::valid($data);
    }

    public function validateExplore(Request $request): ValidationResult
    {
        $payload = $this->decodeJson($request);
        if (!$payload->valid) {
            return $payload;
        }

        $data = $payload->payload;
        $facets = $data['resultType']['facets'] ?? null;

        if (!\is_array($facets)) {
            return ValidationResult::invalid('Explore resultType.facets is required.');
        }

        return ValidationResult::valid([
            'query' => \is_array($data['query'] ?? null) ? $data['query'] : [],
            'facets' => $facets,
        ]);
    }

    public function validateList(Request $request): ValidationResult
    {
        return ValidationResult::valid([
            'filter' => $this->parseListFilter((string) $request->query->get('filter', '')),
            'pageSize' => $this->pageSize($request->query->get('pageSize', 20), 20),
            'pageToken' => \is_string($request->query->get('pageToken')) ? $request->query->get('pageToken') : null,
        ]);
    }

    private function decodeJson(Request $request): ValidationResult
    {
        try {
            $decoded = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ValidationResult::invalid('Malformed JSON request body.');
        }

        if (!\is_array($decoded)) {
            return ValidationResult::invalid('JSON request body must be an object.');
        }

        return ValidationResult::valid($decoded);
    }

    private function pageSize(mixed $value, int $default): int
    {
        $pageSize = filter_var($value, \FILTER_VALIDATE_INT);
        if (false === $pageSize || $pageSize < 1) {
            return $default;
        }

        return min($pageSize, 100);
    }

    /**
     * @return array<string, list<string>>
     */
    private function parseListFilter(string $filter): array
    {
        if ('' === trim($filter)) {
            return [];
        }

        $parsed = [];
        foreach (explode(',', $filter) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            $key = \is_string($key) ? trim($key) : '';
            $value = \is_string($value) ? trim($value) : '';

            if ('' === $key || '' === $value) {
                continue;
            }

            $parsed[$key][] = $value;
        }

        return $parsed;
    }
}
