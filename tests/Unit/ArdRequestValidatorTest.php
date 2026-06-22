<?php

declare(strict_types=1);

use Swag\AgenticResourceDiscovery\Ard\Http\ArdRequestValidator;
use Symfony\Component\HttpFoundation\Request;

return [
    'search validator rejects malformed json' => static function (): void {
        $validator = new ArdRequestValidator();

        $result = $validator->validateSearch(Request::create('/ard/search', 'POST', [], [], [], [], '{'));

        assert_true(!$result->valid, 'Expected invalid result.');
        assert_same('INVALID_ARGUMENT', $result->errorCode);
    },

    'search validator requires query text' => static function (): void {
        $validator = new ArdRequestValidator();

        $result = $validator->validateSearch(Request::create('/ard/search', 'POST', [], [], [], [], '{"query":{}}'));

        assert_true(!$result->valid, 'Expected invalid result.');
        assert_same('Search query.text is required.', $result->message);
    },

    'search validator rejects unsupported federation and clamps page size' => static function (): void {
        $validator = new ArdRequestValidator();

        $invalidFederation = $validator->validateSearch(Request::create('/ard/search', 'POST', [], [], [], [], '{"query":{"text":"shop"},"federation":"remote"}'));
        $largePage = $validator->validateSearch(Request::create('/ard/search', 'POST', [], [], [], [], '{"query":{"text":"shop"},"pageSize":250}'));

        assert_true(!$invalidFederation->valid, 'Expected invalid federation.');
        assert_true($largePage->valid, 'Expected large page request to be valid after clamping.');
        assert_same(100, $largePage->payload['pageSize']);
    },
];
