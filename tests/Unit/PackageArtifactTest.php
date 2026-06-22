<?php

declare(strict_types=1);

return [
    'package script builds named plugin zip and excludes development files' => static function (): void {
        $script = file_get_contents(__DIR__.'/../../bin/build-zip.sh');

        assert_true(false !== $script, 'Expected bin/build-zip.sh to be readable.');
        assert_true(str_contains($script, 'SwagAgenticResourceDiscovery.zip'), 'Expected stable plugin zip name.');
        assert_true(str_contains($script, 'cp -R src'), 'Expected source directory packaging.');
        assert_true(str_contains($script, 'cp composer.json'), 'Expected composer metadata packaging.');
        assert_true(str_contains($script, '--exclude=.github/*'), 'Expected GitHub workflow exclusion.');
        assert_true(str_contains($script, '--exclude=SwagAgenticResourceDiscovery/.github/*'), 'Expected prefixed GitHub workflow exclusion.');
        assert_true(str_contains($script, '--exclude=tests/*'), 'Expected tests exclusion.');
        assert_true(str_contains($script, '--exclude=tools/*'), 'Expected tools exclusion.');
        assert_true(str_contains($script, 'SwagAgenticResourceDiscovery/'), 'Expected plugin directory as archive root.');
    },

    'github actions uploads plugin zip artifact on main' => static function (): void {
        $workflow = file_get_contents(__DIR__.'/../../.github/workflows/build-plugin-zip.yml');

        assert_true(false !== $workflow, 'Expected build-plugin-zip workflow to be readable.');
        assert_true(str_contains($workflow, 'branches:'), 'Expected branch trigger.');
        assert_true(str_contains($workflow, '- main'), 'Expected main branch trigger.');
        assert_true(str_contains($workflow, 'docker compose run --rm qa'), 'Expected workflow to run QA.');
        assert_true(str_contains($workflow, 'bin/build-zip.sh'), 'Expected workflow to call package script.');
        assert_true(str_contains($workflow, 'actions/upload-artifact@v4'), 'Expected artifact upload action.');
        assert_true(str_contains($workflow, 'dist/SwagAgenticResourceDiscovery.zip'), 'Expected plugin zip artifact path.');
    },

    'readme documents downloadable plugin zip artifact' => static function (): void {
        $readme = file_get_contents(__DIR__.'/../../README.md');

        assert_true(false !== $readme, 'Expected README.md to be readable.');
        assert_true(str_contains($readme, 'Build Plugin Zip'), 'Expected workflow name in README.');
        assert_true(str_contains($readme, 'SwagAgenticResourceDiscovery.zip'), 'Expected zip filename in README.');
        assert_true(str_contains($readme, 'docker compose run --rm qa bin/build-zip.sh'), 'Expected local package command in README.');
    },
];
