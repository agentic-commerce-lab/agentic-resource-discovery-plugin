# Agentic Resource Discovery for Shopware

This repository contains a Shopware 6 plugin for Agentic Resource Discovery (ARD).

ARD lets clients discover agent-facing resources, registries, and protocol
surfaces. 

In Agentic Commerce, buyers increasingly delegate shopping, comparison,
procurement, replenishment, and checkout tasks to AI agents. Those agents need a
reliable way to understand which commerce capabilities a storefront exposes,
where to find them, and which protocol should be used to interact with them.
This plugin helps a Shopware storefront publish that discovery layer while
keeping the actual commerce operations inside the systems and protocols the
merchant controls.

For more background on ARD, see Google's announcement:
https://developers.googleblog.com/announcing-the-agentic-resource-discovery-specification/

## Installation

Install this repository as a Shopware platform plugin:

```bash
mkdir -p custom/plugins
git clone <repository-url> custom/plugins/SwagAgenticResourceDiscovery
bin/console plugin:refresh
bin/console plugin:install --activate SwagAgenticResourceDiscovery
bin/console cache:clear
```

For package installs, build or download the extension zip, then install it with
Shopware's regular plugin installation flow.

## Downloadable Plugin Zip

Pushes to `main` run the `Build Plugin Zip` GitHub Actions workflow. The
workflow runs Docker QA, builds `dist/SwagAgenticResourceDiscovery.zip`, and
publishes it to the latest main release:

https://github.com/agentic-commerce-lab/agentic-resource-discovery-plugin/releases/tag/latest-main

The `latest-main` release is updated after every successful push build on
`main`, so it is the easiest place to download the current installable zip.

To install from CI:

1. Open the `latest-main` release.
2. Download `SwagAgenticResourceDiscovery.zip` from the release assets.
3. Upload/install `SwagAgenticResourceDiscovery.zip` through Shopware's plugin
   installation flow.

Build the same zip locally:

```bash
docker compose run --rm qa bin/build-zip.sh
```

## Instructions

After installation, enable the plugin in Shopware Admin and configure it under
the plugin settings. For most shops the defaults are enough to publish a basic
ARD catalog.

Open `https://shop.example/.well-known/ai-catalog.json` in a browser, replacing
`shop.example` with your storefront domain. This is the public entry point for
AI agents. It advertises the Shopware ARD registry and the resources agents can
discover for the storefront.

To add your own resources, paste JSON into the `staticEntriesJson` plugin
configuration field. Use this when you want ARD to advertise an OpenAPI file,
MCP server card, documentation endpoint, or another agent-facing resource that
is not provided by this plugin automatically.

To use this plugin with `shopware/agentic-commerce`:

1. Install and activate both plugins.
2. Configure Agentic Commerce UCP for the storefront sales channel.
3. Enable UCP in the Agentic Commerce configuration.
4. Clear the Shopware cache.
5. Open `/.well-known/ai-catalog.json` on the same storefront domain.

When Agentic Commerce UCP is active, the ARD catalog should include additional
entries such as `Shopware UCP Profile`, `Shopware Agentic Discovery Guide`, and
`Shopware LLM Instructions`. If Agentic Commerce MCP is enabled and the
Shopware runtime supports Store API MCP, the catalog also includes
`Shopware UCP MCP`.

If those entries do not appear, confirm that both plugins are active, UCP is
enabled for the same sales channel, and you are opening the catalog on the same
storefront domain where Agentic Commerce is configured. The ARD plugin only
advertises Agentic Commerce resources when Agentic Commerce itself is active for
that storefront.

## Configuration Keys

The plugin configuration currently defines these keys:

| Key | Purpose | Default |
| --- | --- | --- |
| `enabled` | Enables public ARD endpoints. Disabled endpoints return HTTP `404`. | `true` |
| `hostDisplayName` | Human-readable host name in `ai-catalog.json`. | `Shopware Agentic Resource Discovery` |
| `documentationUrl` | Optional host documentation URL in `ai-catalog.json`. | empty |
| `staticEntriesJson` | Optional JSON for additional static catalog entries. | empty |
| `referralsJson` | Optional JSON for registry referrals returned by federated search. | empty |

At runtime the plugin reads these values from Shopware's `SystemConfigService`.
When a sales-channel context is available, sales-channel scoped plugin settings
are used first and global plugin settings are used as fallback.

## Current State

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

The registry endpoints are also implemented:

- `POST /ard/search`
- `POST /ard/explore`
- `GET /ard/agents`

`/ard/search` validates JSON request bodies, requires `query.text`, clamps
`pageSize` to `100`, supports `federation` values `auto`, `referrals`, and
`none`, and returns catalog entries with ARD `score` and `source` fields.
Search uses deterministic lexical matching.

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
- Google ARD announcement: https://developers.googleblog.com/announcing-the-agentic-resource-discovery-specification/
- Conformance tool: https://github.com/ards-project/ard-spec/tree/main/conformance

## Static Catalog Entries

Static entries let a merchant or implementation engineer publish additional ARD
resources without writing a new catalog provider. The current provider reads
`staticEntriesJson` from Shopware plugin configuration, with sales-channel
overrides applied when the request has a sales-channel context.

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

## Registry Referrals

`referralsJson` configures referral registries returned by `POST /ard/search`
when the request uses `"federation": "referrals"`. It accepts either a JSON
array:

```json
[
  {
    "url": "https://registry.example.com/ard",
    "displayName": "Partner Registry"
  }
]
```

or an object with a `referrals` array:

```json
{
  "referrals": [
    {
      "url": "https://registry.example.com/ard",
      "displayName": "Partner Registry"
    }
  ]
}
```

Invalid referral JSON is ignored and the endpoint continues with local results.

## Agentic Commerce Bridge

The plugin has a soft bridge for `shopware/agentic-commerce`. The ARD catalog
provider depends on an internal `AgenticCommerceBridgeInterface`, so this plugin
does not require the Agentic Commerce plugin at compile time.

Current behavior:

- When Agentic Commerce is absent, optional service references resolve to
  `null`, no Agentic Commerce entries are emitted, and the ARD plugin still
  boots.
- When Agentic Commerce is installed and UCP is active for the current sales
  channel, the catalog advertises
  `/.well-known/ucp` as a `Shopware UCP Profile` entry.
- When Agentic Commerce config enables MCP and the Shopware runtime supports
  Store API MCP, the catalog advertises `/ucp/mcp` as a `Shopware UCP MCP`
  entry.
- When UCP is active, the catalog advertises `/agents.md` and `/llms.txt`.
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

The bridge reads the companion plugin's `UcpConfigService` for the current
sales channel and uses its `ShopwareVersionDetector` to avoid advertising MCP
when the Store API MCP runtime is unavailable.

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
The Docker Compose `qa` and `test` services run `composer install` first, so a
fresh checkout does not need a pre-existing `.tools/vendor` directory.

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

## Manual Testing and Release

Manual runtime checks are documented in
[docs/manual-testing.md](docs/manual-testing.md).

Release gates are documented in
[docs/release-checklist.md](docs/release-checklist.md).

## Contributing and Feedback

ARD is an emerging discovery layer for agent-facing resources, and this plugin
is intended to evolve with feedback from developers, merchants, partners, and
researchers working on agentic commerce.

If you are integrating Shopware with AI agents, experimenting with ARD, or
testing this plugin against your own storefront, please open an issue or
discussion with questions, implementation feedback, interoperability findings,
or suggestions for what the plugin should support next.

## License

MIT
