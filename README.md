# Agentic Resource Discovery for Shopware

This repository contains a Shopware 6 plugin for Agentic Resource Discovery (ARD).

## Current MVP State

The plugin currently provides the static ARD catalog building blocks for
`/.well-known/ai-catalog.json`:

- a schema-shaped `specVersion: "1.0"` manifest
- host display metadata
- catalog entries from registered providers
- a default self-entry for the Shopware ARD registry at `/ard`
- disabled behavior that returns HTTP `404`

The catalog controller uses Symfony `JsonResponse` / `Response` and Symfony
routing attributes. The Docker QA environment installs the Symfony runtime
packages needed for those tests through Composer.

## Docker QA

The local workflow does not require PHP or Composer on the host. Use Docker Compose:

```bash
docker compose run --rm qa
```

Run only the unit test harness:

```bash
docker compose run --rm test
```

Open a PHP shell in the same container image:

```bash
docker compose run --rm --profile dev shell
```

The `qa` command runs PHP syntax linting and the unit test harness through Composer scripts.

Composer dependencies are installed into `.tools/vendor`, which is ignored by
git. The lockfile is resolved with Composer platform PHP `8.1.0` so dependency
selection matches the plugin's declared PHP support even when Docker runs a
newer PHP CLI image.