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

The MVP registry endpoints are also implemented:

- `POST /ard/search`
- `POST /ard/explore`
- `GET /ard/agents`

`/ard/search` validates JSON request bodies, requires `query.text`, clamps
`pageSize` to `100`, supports `federation` values `auto`, `referrals`, and
`none`, and returns catalog entries with ARD `score` and `source` fields.
Search uses deterministic lexical matching for the MVP.

`/ard/agents` supports deterministic listing with `pageSize`, `pageToken`, and
a simple comma-separated filter syntax such as:

```text
type=application/ai-registry+json,tags=shopware
```

`/ard/explore` supports facet aggregation over the matched catalog entries.
Filters support scalar fields, arrays, dot-paths such as `metadata.protocol`,
nested arrays such as `trustManifest.attestations.type`, and the derived
`publisher` field from `urn:air:<publisher>:...` identifiers.

## ARD Spec Mapping

The plugin exposes ARD under a Shopware-owned registry base path. The ARD spec
defines registry paths relative to a registry base URL, so this plugin maps:

| ARD spec path | Plugin path | Status |
| --- | --- | --- |
| `/.well-known/ai-catalog.json` | `/.well-known/ai-catalog.json` | Implemented |
| `POST /search` | `POST /ard/search` | Implemented |
| `POST /explore` | `POST /ard/explore` | Implemented |
| `GET /agents` | `GET /ard/agents` | Implemented |

The `/.well-known/ai-catalog.json` manifest advertises the Shopware registry
entry with media type `application/ai-registry+json` and URL `/ard`. Clients
then call the registry-relative endpoints under `/ard`.

Relevant ARD references:

- Main ARD spec: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md
- Capability manifest: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#41-the-capability-manifest-ai-catalogjson
- Catalog entry object: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#42-catalog-entry-object
- Query model and filter semantics: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#71-the-query-model
- Search endpoint: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#72-search-post-search
- Explore endpoint: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#73-explore-post-explore--optional
- List endpoint: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#74-list-get-agents--optional
- Federation modes: https://github.com/ards-project/ard-spec/blob/main/spec/ard.md#8-federation
- OpenAPI schema: https://github.com/ards-project/ard-spec/blob/main/spec/schemas/ard.openapi.yaml
- `ai-catalog` JSON Schema: https://github.com/ards-project/ard-spec/blob/main/spec/schemas/ai-catalog.schema.json
- Conformance tool: https://github.com/ards-project/ard-spec/tree/main/conformance

MVP limitations:

- Search scoring is lexical and deterministic, not embedding-based semantic
  ranking.
- `federation: auto` searches local entries only.
- `federation: referrals` can return configured referrals, but config-backed
  referrals are planned for a later epic.
- `GET /ard/agents` supports a simple comma-separated `key=value` filter syntax,
  not the full optional EBNF-style list filter.
- Official ARD conformance tests are planned for the conformance epic.

## Static Catalog Entries

Static entries let a merchant or implementation engineer publish additional ARD
resources without writing a new catalog provider. The current provider reads
`staticEntriesJson` from the active `ArdConfig`; wiring that value to Shopware's
`SystemConfigService` is still pending.

Accepted JSON shape: an array of entries:

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

Accepted JSON shape: a manifest-like object with `entries`:

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

Validation rules:

- `identifier`, `displayName`, and `type` must be non-empty strings.
- `identifier` must match `urn:air:<publisher>:<namespace>:<agent-name>`.
- exactly one of `url` or `data` must be present.
- `url`, when present, must be an absolute URL.
- `data`, when present, must be a JSON object.
- `representativeQueries`, when present, must contain 2 to 5 non-empty strings.

Invalid static entries are omitted from the public catalog. The provider records
a warning with the invalid entry index and continues rendering the remaining
valid entries.

## Agentic Commerce Bridge

The plugin has a soft bridge for `shopware/agentic-commerce`. The ARD catalog
provider depends on an internal `AgenticCommerceBridgeInterface`, so this plugin
does not require the Agentic Commerce plugin at compile time.

Current behavior:

- When Agentic Commerce is absent, a null bridge returns no Agentic Commerce
  entries and the ARD plugin still boots.
- When a bridge reports UCP active, the catalog advertises
  `/.well-known/ucp` as a `Shopware UCP Profile` entry.
- When a bridge reports MCP available, the catalog advertises `/ucp/mcp` as a
  `Shopware UCP MCP` entry.
- When a bridge reports native discovery routes available, the catalog
  advertises `/agents.md` and `/llms.txt`.
- When UCP is inactive, no Agentic Commerce UCP/MCP/native discovery entries are
  advertised.

The bridge uses UCP descriptor names such as:

- `dev.ucp.shopping.catalog`
- `dev.ucp.shopping.cart`
- `dev.ucp.shopping.checkout`
- `dev.ucp.shopping.order`

Those descriptor names match the capability names exposed by the Agentic
Commerce plugin's UCP capability catalog. MCP tool-style capabilities are
derived from the active UCP descriptors for ARD filtering.

Pending integration work: add the concrete adapter that reads the real
`shopware/agentic-commerce` services, sales-channel UCP config, MCP transport
availability, and native discovery route availability.

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

## ARD Conformance

The Docker QA image includes `python3` so it can run the official ARD
conformance CLI once the spec repository is available locally.

Clone or vendor the ARD spec repository into `tools/ard-spec`:

```bash
git clone https://github.com/ards-project/ard-spec.git tools/ard-spec
```

Run conformance checks against a live Shopware base URL:

```bash
docker compose run --rm qa bin/ard-conformance.sh https://example.com
```

The helper validates:

- `https://example.com/.well-known/ai-catalog.json`
- `https://example.com/ard`

The target URL must be a running Shopware storefront with this plugin installed
and active. Local unit tests do not replace this live conformance pass.

GitHub Actions runs the same conformance helper when the repository variable
`ARD_CONFORMANCE_BASE_URL` is set. Configure it to the public base URL of a live
Shopware test storefront with this plugin installed. If the variable is not set,
the workflow still runs Docker QA and skips live conformance.
