# Agentic Resource Discovery for Shopware

This repository contains a Shopware 6 plugin for Agentic Resource Discovery (ARD).

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
