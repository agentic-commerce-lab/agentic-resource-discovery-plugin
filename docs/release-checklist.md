# Release Checklist

Run this checklist before tagging or packaging a release.

- [ ] `composer ci`
- [ ] `docker compose run --rm qa`
- [ ] Shopware lane install test
- [ ] Shopware lane activation test
- [ ] Browser check for `/.well-known/ai-catalog.json`
- [ ] Curl check for `POST /ard/search`
- [ ] Curl check for `POST /ard/explore`
- [ ] Curl check for `GET /ard/agents`
- [ ] ARD conformance manifest test
- [ ] ARD conformance registry test
- [ ] Agentic Commerce bridge test with UCP enabled, when the companion plugin is available
- [ ] Agentic Commerce bridge test with UCP disabled, when the companion plugin is available
- [ ] package zip install test

Packaging metadata lives in `.shopware-extension.yml`.

The package zip should exclude development-only files such as tests, docs,
Docker files, GitHub Actions, and local tooling caches.
