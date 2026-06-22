<?php

declare(strict_types=1);

return [
    'conformance script runs manifest and registry checks against base url' => static function (): void {
        $script = file_get_contents(__DIR__.'/../../bin/ard-conformance.sh');

        assert_true(false !== $script, 'Expected bin/ard-conformance.sh to be readable.');
        assert_true(str_contains($script, 'python3 tools/ard-spec/conformance/bin/conformance-test manifest "$BASE_URL/.well-known/ai-catalog.json"'), 'Expected manifest conformance command.');
        assert_true(str_contains($script, 'python3 tools/ard-spec/conformance/bin/conformance-test registry "$BASE_URL/ard"'), 'Expected registry conformance command.');
    },

    'ard spec tooling readme documents setup and docker usage' => static function (): void {
        $readme = file_get_contents(__DIR__.'/../../tools/ard-spec/README.md');

        assert_true(false !== $readme, 'Expected tools/ard-spec/README.md to be readable.');
        assert_true(str_contains($readme, 'git clone https://github.com/ards-project/ard-spec.git tools/ard-spec'), 'Expected clone instructions.');
        assert_true(str_contains($readme, 'docker compose run --rm qa bin/ard-conformance.sh https://example.com'), 'Expected Docker conformance example.');
    },

    'github actions runs conformance when base url variable is configured' => static function (): void {
        $workflow = file_get_contents(__DIR__.'/../../.github/workflows/qa.yml');

        assert_true(false !== $workflow, 'Expected GitHub Actions QA workflow to be readable.');
        assert_true(str_contains($workflow, 'vars.ARD_CONFORMANCE_BASE_URL'), 'Expected workflow to reference conformance base URL variable.');
        assert_true(str_contains($workflow, 'repository: ards-project/ard-spec'), 'Expected workflow to checkout ARD spec.');
        assert_true(str_contains($workflow, 'bin/ard-conformance.sh "${{ vars.ARD_CONFORMANCE_BASE_URL }}"'), 'Expected workflow to run conformance helper.');
    },
];
