<?php

declare(strict_types=1);

return [
    'manual testing guide documents browser and curl checks' => static function (): void {
        $manual = file_get_contents(__DIR__.'/../../docs/manual-testing.md');

        assert_true(false !== $manual, 'Expected docs/manual-testing.md to be readable.');
        assert_true(str_contains($manual, 'GET /.well-known/ai-catalog.json'), 'Expected catalog endpoint manual check.');
        assert_true(str_contains($manual, 'POST /ard/search'), 'Expected search endpoint manual check.');
        assert_true(str_contains($manual, 'POST /ard/explore'), 'Expected explore endpoint manual check.');
        assert_true(str_contains($manual, 'GET /ard/agents'), 'Expected list endpoint manual check.');
        assert_true(str_contains($manual, 'bin/ard-conformance.sh'), 'Expected conformance command.');
    },

    'release checklist documents required packaging gates' => static function (): void {
        $checklist = file_get_contents(__DIR__.'/../../docs/release-checklist.md');

        assert_true(false !== $checklist, 'Expected docs/release-checklist.md to be readable.');
        assert_true(str_contains($checklist, 'composer ci'), 'Expected composer ci gate.');
        assert_true(str_contains($checklist, 'Shopware lane install test'), 'Expected Shopware lane install gate.');
        assert_true(str_contains($checklist, 'ARD conformance manifest test'), 'Expected manifest conformance gate.');
        assert_true(str_contains($checklist, 'ARD conformance registry test'), 'Expected registry conformance gate.');
        assert_true(str_contains($checklist, 'package zip install test'), 'Expected package zip install gate.');
    },

    'readme documents install configuration and release docs' => static function (): void {
        $readme = file_get_contents(__DIR__.'/../../README.md');

        assert_true(false !== $readme, 'Expected README.md to be readable.');
        assert_true(str_contains($readme, '## Installation'), 'Expected installation section.');
        assert_true(str_contains($readme, '## Configuration Keys'), 'Expected configuration section.');
        assert_true(str_contains($readme, 'docs/manual-testing.md'), 'Expected manual testing link.');
        assert_true(str_contains($readme, 'docs/release-checklist.md'), 'Expected release checklist link.');
    },

    'composer exposes ci script and shopware extension metadata exists' => static function (): void {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        $extension = file_get_contents(__DIR__.'/../../.shopware-extension.yml');

        assert_same('@qa', $composer['scripts']['ci']);
        assert_true(false !== $extension, 'Expected .shopware-extension.yml to be readable.');
        assert_true(str_contains($extension, 'shopwareVersionConstraint: ">=6.5.0 <6.8.0"'), 'Expected Shopware version constraint.');
        assert_true(str_contains($extension, 'composer:'), 'Expected Composer packaging config.');
    },
];
