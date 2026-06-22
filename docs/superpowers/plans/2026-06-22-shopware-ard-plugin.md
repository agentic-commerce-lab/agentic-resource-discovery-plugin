# Shopware ARD Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an installable Shopware 6 plugin that publishes Agentic Resource Discovery (ARD) catalog and registry endpoints for a storefront, with optional resource entries sourced from `shopware/agentic-commerce` when that plugin is installed.

**Architecture:** The plugin is a discovery layer only. It publishes ARD `ai-catalog.json` and registry search/list/explore endpoints from sales-channel-aware catalog entries, but it never reimplements commerce execution. Local static entries are always available; Agentic Commerce/UCP/MCP entries are added through a soft optional bridge when the `SwagAgenticCommerce` services are present.

**Tech Stack:** Shopware 6 plugin, PHP 8.1+, Symfony routing/controllers, Shopware sales-channel context, PHPUnit, PHPStan, ARD v0.9 conformance CLI.

---

## Research Summary

### ARD v0.9 Requirements

- ARD is discovery before invocation. The discovered resource is invoked through its own native protocol.
- Static publishing uses `/.well-known/ai-catalog.json`.
- Catalog entries require `identifier`, `displayName`, and `type`.
- Each catalog entry must contain exactly one of `url` or `data`.
- Identifiers must use the current `urn:air:<publisher>:<namespace>:<agent-name>` form.
- `representativeQueries` should contain 2-5 examples when present.
- A dynamic registry must expose `POST /search`.
- `POST /explore` and `GET /agents` are optional, but low cost for this MVP.
- `application/ai-registry+json` entries advertise a registry base URL.
- Search accepts:

```json
{
  "query": {
    "text": "find shopping tools",
    "filter": {
      "type": ["application/mcp-server-card+json"]
    }
  },
  "federation": "none",
  "pageSize": 10
}
```

- Search returns:

```json
{
  "results": [
    {
      "identifier": "urn:air:example.com:shopware:ard-registry",
      "displayName": "Shopware ARD Registry",
      "type": "application/ai-registry+json",
      "url": "https://example.com/ard",
      "score": 100,
      "source": "https://example.com/ard"
    }
  ]
}
```

### Shopware Agentic Commerce Findings

- The private `shopware/agentic-commerce` repository is a Shopware plugin for UCP and native agentic discovery.
- It already exposes UCP profile/transports and a trunk/6.7 MCP proxy at `/ucp/mcp` when the active sales channel enables MCP.
- It has native discovery fallback routes for `/agents.md` and `/llms.txt`.
- It keeps commerce behavior behind Store API/UCP adapters. The ARD plugin should point to those endpoints, not duplicate catalog/cart/checkout/order logic.
- Useful classes/services to bridge when present:
  - `Swag\AgenticCommerce\Ucp\Config\UcpConfigService`
  - `Swag\AgenticCommerce\Ucp\Profile\ProfilePreviewBuilder`
  - `Swag\AgenticCommerce\Ucp\SalesChannel\SalesChannelDomainResolver`
  - `Swag\AgenticCommerce\Ucp\Capability\UcpCapabilityCatalog`
- UCP capabilities found:
  - `dev.ucp.shopping.catalog`
  - `dev.ucp.shopping.cart`
  - `dev.ucp.shopping.discount`
  - `dev.ucp.shopping.checkout`
  - `dev.ucp.shopping.order`
  - `dev.ucp.common.identity_linking`
  - `dev.ucp.shopping.payment_tokenization`

### MVP Decisions

- Build a separate plugin named `SwagAgenticResourceDiscovery`.
- Soft-detect Agentic Commerce. Do not add a Composer hard dependency.
- Publish only public, unauthenticated discovery endpoints in the MVP.
- Generate entries per sales-channel domain so URLs match the host being queried.
- Implement lexical scoring and structured filtering in memory. No database index or embeddings in the MVP.
- Return `federation: auto` as local-only for MVP and support `referrals` only if configured static referrals exist.
- Keep an Administration UI out of MVP. Use plugin config XML and later add admin screens if needed.

## Planned File Structure

- `composer.json`: Shopware plugin metadata and local tooling scripts.
- `src/SwagAgenticResourceDiscovery.php`: plugin base class and privilege/config bootstrap.
- `src/Resources/config/routes.php`: imports controller attributes.
- `src/Resources/config/services.php`: service registration, optional Agentic Commerce bridge registration.
- `src/Resources/config/config.xml`: merchant config for enablement, host display name, documentation URL, static referrals, and optional static entries JSON.
- `src/Ard/Model/CatalogEntry.php`: immutable catalog entry value object.
- `src/Ard/Model/AiCatalogManifest.php`: immutable manifest value object.
- `src/Ard/Catalog/CatalogEntryProviderInterface.php`: provider contract.
- `src/Ard/Catalog/CompositeCatalogEntryProvider.php`: merges provider output.
- `src/Ard/Catalog/CoreShopwareResourceProvider.php`: registry self-entry and optional built-in discovery-file entries.
- `src/Ard/Catalog/StaticConfigResourceProvider.php`: validates and exposes merchant-configured static entries.
- `src/Ard/Catalog/AgenticCommerceResourceProvider.php`: optional provider for UCP/MCP/native discovery entries.
- `src/Ard/Catalog/AiCatalogBuilder.php`: builds `specVersion`, `host`, and `entries`.
- `src/Ard/Registry/RegistrySearchService.php`: shared search/filter/page/facet logic.
- `src/Ard/Registry/FilterMatcher.php`: dot-path filter implementation with OR-within-key and AND-across-keys semantics.
- `src/Ard/Registry/TextMatcher.php`: simple lexical score over display name, description, tags, capabilities, and representative queries.
- `src/Ard/Http/ArdRequestValidator.php`: validates request payloads and returns spec error codes.
- `src/Ard/Http/ArdErrorResponseFactory.php`: emits ARD error envelopes.
- `src/Ard/Api/AiCatalogController.php`: `GET /.well-known/ai-catalog.json`.
- `src/Ard/Api/RegistryController.php`: `POST /ard/search`, `POST /ard/explore`, `GET /ard/agents`.
- `tests/Unit/*`: unit tests for builders, providers, filters, validators, and search.
- `tests/Integration/*`: route/controller tests with fake sales channel context.
- `tests/fixtures/*`: schema-compatible sample manifests and requests.

## Epic 1: Plugin Skeleton and Runtime Wiring

**Outcome:** The repository installs as a Shopware plugin and exposes an empty but valid ARD catalog.

- [ ] Create `composer.json` with:
  - package name `shopware/agentic-resource-discovery`
  - type `shopware-platform-plugin`
  - PHP constraint `^8.1`
  - namespace `Swag\AgenticResourceDiscovery\`
  - plugin class `Swag\AgenticResourceDiscovery\SwagAgenticResourceDiscovery`
  - scripts for unit tests, PHPStan, and CS.
- [ ] Create `src/SwagAgenticResourceDiscovery.php` extending `Shopware\Core\Framework\Plugin`.
- [ ] Create `src/Resources/config/routes.php` importing `src/Ard/Api/` attribute routes.
- [ ] Create `src/Resources/config/services.php` with autowire/autoconfigure defaults, service loading excluding `Resources`, controller tags, and tagged catalog providers.
- [ ] Create `src/Resources/config/config.xml` with:
  - `enabled` boolean, default `true`
  - `hostDisplayName` text, default `Shopware Agentic Resource Discovery`
  - `documentationUrl` URL, optional
  - `staticEntriesJson` textarea, optional
  - `referralsJson` textarea, optional
- [ ] Add a minimal `CoreShopwareResourceProvider` that always returns a self registry entry:

```json
{
  "identifier": "urn:air:example.com:shopware:ard-registry",
  "displayName": "Shopware ARD Registry",
  "type": "application/ai-registry+json",
  "url": "https://example.com/ard",
  "description": "ARD registry for this Shopware sales channel.",
  "tags": ["shopware", "commerce", "registry"],
  "capabilities": ["ard.search", "ard.explore", "ard.list"],
  "representativeQueries": [
    "find shopware commerce resources",
    "discover agentic shopping capabilities"
  ]
}
```

- [ ] Add unit tests proving:
  - plugin services compile in isolation with fake config.
  - generated identifiers use `urn:air`, not `urn:ai`.
  - generated entries contain exactly one of `url` or `data`.

## Epic 2: Static `/.well-known/ai-catalog.json`

**Outcome:** Crawlers can discover a schema-valid AI catalog manifest for each storefront domain.

- [x] Implement `CatalogEntry` with `toArray()` that omits null/empty optional fields.
- [x] Implement `AiCatalogManifest` with:
  - `specVersion: "1.0"`
  - `host.displayName`
  - optional `host.documentationUrl`
  - `entries`
- [x] Implement `AiCatalogBuilder` that receives the active request base URL and sales channel context.
- [x] Implement `AiCatalogController`:
  - route: `GET /.well-known/ai-catalog.json`
  - scopes: storefront route scope
  - auth: false
  - response content type: `application/json`
  - cache headers: `public, max-age=300`
- [x] Implement config disable behavior:
  - if `enabled=false`, return `404` from all public ARD endpoints.
- [x] Add lightweight controller/model tests for:
  - enabled catalog returns `200`.
  - disabled catalog returns `404`.
  - manifest contains `specVersion`, `host`, and at least the registry self-entry.
- [ ] Add schema validation against `spec/schemas/ai-catalog.schema.json` once the ARD conformance assets are vendored or downloaded in Epic 6.

## Epic 3: Dynamic Registry MVP

**Outcome:** ARD clients can search the Shopware storefront resources through required and optional registry endpoints.

- [x] Implement `RegistryController` routes:
  - `POST /ard/search`
  - `POST /ard/explore`
  - `GET /ard/agents`
- [x] Implement `ArdRequestValidator`:
  - reject malformed JSON with `400 INVALID_ARGUMENT`.
  - require `query.text` for `/search`.
  - reject unsupported federation values outside `auto`, `referrals`, `none`.
  - clamp `pageSize` to max `100`.
- [x] Implement `FilterMatcher`:
  - dot-path traversal.
  - array values match if any element matches.
  - values within one filter key are OR.
  - different filter keys are AND.
  - support at least `type`, `tags`, `capabilities`, `publisher`, `metadata.*`, and `trustManifest.attestations.type`.
- [x] Implement `TextMatcher`:
  - tokenize lowercase alphanumeric words.
  - score over `displayName`, `description`, `tags`, `capabilities`, and `representativeQueries`.
  - return integer `0-100`.
  - include all filtered entries with score `100` for empty text in explore/list flows.
- [x] Implement `RegistrySearchService`:
  - returns search `results` with `score` and `source`.
  - returns `referrals` only when request `federation` is `referrals` and referrals are configured.
  - implements simple offset page tokens as base64 JSON: `{"offset":10}`.
- [x] Implement `GET /ard/agents`:
  - accepts `pageSize` and `pageToken`.
  - accepts a minimal MVP `filter` syntax of comma-separated `key=value` pairs, for example `type=application/ai-registry+json,tags=shopware`.
  - returns `{ "items": [], "total": 0 }`.
- [x] Implement `POST /ard/explore`:
  - supports facets over the current matched set.
  - returns `resultType: "facets"`.
- [x] Add unit tests for:
  - invalid search request errors.
  - search text relevance.
  - filters on scalar, arrays, nested arrays, and derived publisher.
  - pagination token round trip.
  - explore facet counts and `otherCount`.

## Epic 4: Static Merchant Entries

**Outcome:** A merchant or implementation engineer can publish additional ARD entries without code changes.

- [x] Implement `StaticConfigResourceProvider`.
- [x] Read `staticEntriesJson` from the active `ArdConfig`.
- [x] Add the Shopware `SystemConfigService` adapter that hydrates `ArdConfig::staticEntriesJson` from plugin system config.
- [x] Accept either:

```json
[
  {
    "identifier": "urn:air:example.com:shopware:custom-api",
    "displayName": "Custom Product Advice API",
    "type": "application/openapi+json",
    "url": "https://example.com/openapi.json",
    "description": "Product advice API for agents.",
    "representativeQueries": [
      "get product advice",
      "find compatible products"
    ]
  }
]
```

or:

```json
{
  "entries": [
    {
      "identifier": "urn:air:example.com:shopware:custom-api",
      "displayName": "Custom Product Advice API",
      "type": "application/openapi+json",
      "url": "https://example.com/openapi.json"
    }
  ]
}
```

- [x] Validate each static entry:
  - required fields exist.
  - exactly one of `url` or `data`.
  - identifier matches `^urn:air:[a-zA-Z0-9.-]+(:[a-zA-Z0-9._-]+)+$`.
  - `representativeQueries`, if present, contains 2-5 strings.
- [x] On invalid config:
  - log a warning with the invalid entry index.
  - omit invalid entries from public output.
  - do not break the catalog endpoint.
- [x] Add unit tests for valid array shape, valid manifest shape, invalid entry omission, and warning behavior.

## Epic 5: Optional Agentic Commerce Bridge

**Outcome:** When `shopware/agentic-commerce` is installed and active, ARD exposes its relevant discovery and execution surfaces as catalog entries.

- [x] Register `AgenticCommerceResourceProvider` with a null bridge fallback so the plugin boots when Agentic Commerce is absent.
- [x] Add the concrete Shopware Agentic Commerce bridge and register it only when Agentic Commerce classes/services exist.
- [x] Avoid compile-time hard dependency in the ARD provider by depending only on `AgenticCommerceBridgeInterface`.
- [x] Generate a UCP profile entry when UCP is active for the sales channel:

```json
{
  "identifier": "urn:air:example.com:shopware:ucp-profile",
  "displayName": "Shopware UCP Profile",
  "type": "application/json",
  "url": "https://example.com/.well-known/ucp",
  "description": "Universal Commerce Protocol profile for this Shopware sales channel.",
  "tags": ["shopware", "ucp", "commerce"],
  "capabilities": [
    "dev.ucp.shopping.catalog",
    "dev.ucp.shopping.cart",
    "dev.ucp.shopping.checkout",
    "dev.ucp.shopping.order"
  ],
  "representativeQueries": [
    "discover shopping protocol capabilities",
    "find checkout and cart operations for this shop"
  ],
  "metadata": {
    "shopware.agenticCommerce": true,
    "protocol": "ucp",
    "protocolVersion": "2026-04-08"
  }
}
```

- [x] Generate an MCP entry when Agentic Commerce reports MCP transport available:

```json
{
  "identifier": "urn:air:example.com:shopware:ucp-mcp",
  "displayName": "Shopware UCP MCP",
  "type": "application/mcp-server-card+json",
  "url": "https://example.com/ucp/mcp",
  "description": "MCP transport for Shopware UCP shopping operations.",
  "tags": ["shopware", "ucp", "mcp", "commerce"],
  "capabilities": [
    "catalog.search",
    "catalog.lookup",
    "cart.create",
    "cart.update",
    "checkout.create",
    "checkout.complete",
    "order.get"
  ],
  "representativeQueries": [
    "search products in this shop",
    "create a cart and checkout through shopware"
  ]
}
```

- [x] Generate native discovery document entries when routes are available:
  - `/agents.md` as `text/markdown`
  - `/llms.txt` as `text/plain`
- [x] Derive advertised capabilities from bridge-provided UCP descriptor names, matching `UcpCapabilityCatalog` descriptors.
- [x] Never advertise an Agentic Commerce endpoint if its sales-channel config is inactive.
- [x] Add unit tests with fake bridge services for:
  - Agentic Commerce absent: no bridge provider registered, plugin still boots.
  - UCP active without MCP: UCP profile entry only.
  - UCP active with MCP: UCP profile and MCP entries.
  - UCP inactive: no UCP/MCP entries.

## Epic 6: Conformance and Runtime QA

**Outcome:** The plugin has repeatable validation against ARD and Shopware behavior.

- [x] Add `tools/ard-spec/README.md` documenting how to clone or vendor the ARD conformance tool for local validation.
- [x] Add script `bin/ard-conformance.sh <base-url>` that runs:

```bash
python3 conformance/bin/conformance-test manifest "$1/.well-known/ai-catalog.json"
python3 conformance/bin/conformance-test registry "$1/ard"
```

- [x] Add lightweight controller/service tests covering:
  - route status codes.
  - content types.
  - disabled config.
  - search response structure.
  - list response structure.
  - explore response structure.
- [x] Add static analysis baseline only if needed; prefer fixing issues directly.
- [ ] Manual QA on a Shopware 6.6 or 6.7 lane:
  - install plugin.
  - activate plugin.
  - open `/.well-known/ai-catalog.json`.
  - post to `/ard/search` with `{"query":{"text":"shopping checkout"}}`.
  - run ARD conformance manifest validation.
  - run ARD conformance registry validation.
- [ ] Manual QA with Agentic Commerce installed:
  - enable UCP for a sales channel.
  - enable MCP where supported.
  - confirm UCP/MCP/native discovery entries appear.
  - disable UCP.
  - confirm UCP/MCP entries disappear.

## Epic 7: Documentation and Release Packaging

**Outcome:** The MVP is installable and understandable by a Shopware developer or merchant.

- [x] Write `README.md` with:
  - what ARD is and is not.
  - endpoint list.
  - minimal install instructions.
  - configuration keys.
  - Agentic Commerce bridge behavior.
  - conformance commands.
- [x] Write `docs/manual-testing.md` with exact browser/curl steps.
- [x] Add `.shopware-extension.yml` if packaging through Shopware extension tooling is required.
- [x] Add a release checklist:
  - `composer ci`
  - Shopware lane install test
  - ARD conformance manifest test
  - ARD conformance registry test
  - package zip install test

## Suggested Incremental Order

1. Epic 1: skeleton and valid empty catalog.
2. Epic 2: static `ai-catalog.json` endpoint.
3. Epic 3: required `/ard/search`, then optional `/ard/agents` and `/ard/explore`.
4. Epic 4: configurable static entries.
5. Epic 5: Agentic Commerce bridge.
6. Epic 6: conformance and lane QA.
7. Epic 7: docs and packaging.

This order keeps every step usable: after Epic 2 the shop is crawlable, after Epic 3 it is a minimal ARD registry, after Epic 4 it is merchant-extensible, and after Epic 5 it discovers Agentic Commerce capabilities when present.

## Risks and Guardrails

- ARD is draft v0.9. Keep schema and conformance tool references pinned by commit or copied version for release builds.
- Media types for MCP/A2A are still evolving. Do not hard-fail on unknown media types; validate structure instead.
- Agentic Commerce is private and can change. Keep the bridge optional and covered by tests with fake bridge services.
- Do not expose authenticated UCP behavior directly through ARD. ARD should advertise resource locations and metadata only.
- Avoid semantic/vector search in MVP. Lexical matching is enough for conformance and predictable tests.
- Avoid database storage in MVP unless merchant config proves too limiting.

## Definition of Done

- Plugin installs and activates on a supported Shopware 6 lane.
- `GET /.well-known/ai-catalog.json` returns an ARD/ai-catalog compatible manifest.
- `POST /ard/search` returns spec-shaped search results.
- `GET /ard/agents` and `POST /ard/explore` either pass conformance or return compliant optional-not-supported statuses; this plan implements both.
- Static merchant entries can be added without code changes.
- Agentic Commerce entries appear only when the plugin is installed and the relevant sales-channel capabilities are active.
- ARD conformance CLI passes for manifest and registry against a local Shopware instance.
