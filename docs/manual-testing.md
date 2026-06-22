# Manual Testing

These checks assume a running Shopware 6.6 or 6.7 storefront with the
`SwagAgenticResourceDiscovery` plugin installed and active.

Set the storefront base URL once:

```bash
export SHOP_URL="https://example.com"
```

## Plugin Install Smoke

Run these commands from the Shopware project root:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate SwagAgenticResourceDiscovery
bin/console cache:clear
```

## Browser Checks

Open the catalog manifest in a browser:

```text
GET /.well-known/ai-catalog.json
```

Expected:

- HTTP `200`
- JSON body
- `specVersion` is `1.0`
- `entries` contains `Shopware ARD Registry`
- the registry entry URL ends with `/ard`

## Curl Checks

Fetch the static catalog:

```bash
curl -i "$SHOP_URL/.well-known/ai-catalog.json"
```

Search the registry:

```bash
curl -i \
  -H 'content-type: application/json' \
  -d '{"query":{"text":"shopping checkout"},"pageSize":5,"federation":"none"}' \
  "$SHOP_URL/ard/search"
```

Expected:

- HTTP `200`
- response contains a `results` array
- result items include `identifier`, `displayName`, `type`, `score`, and `source`

Explore facets:

```bash
curl -i \
  -H 'content-type: application/json' \
  -d '{"resultType":{"facets":[{"field":"type"},{"field":"publisher"}]}}' \
  "$SHOP_URL/ard/explore"
```

Expected:

- HTTP `200`
- response contains `resultType: "facets"`
- response contains `facets.type.buckets`

List entries:

```bash
curl -i "$SHOP_URL/ard/agents?pageSize=20"
```

Expected:

- HTTP `200`
- response contains `items`
- response contains `total`

Filter entries:

```bash
curl -i "$SHOP_URL/ard/agents?filter=tags=shopware"
```

Expected:

- HTTP `200`
- every returned item matches the filter.

## Disabled Config Check

Disable ARD endpoints in plugin configuration, clear cache, then repeat:

```text
GET /.well-known/ai-catalog.json
POST /ard/search
POST /ard/explore
GET /ard/agents
```

Expected: each endpoint returns HTTP `404`.

## Agentic Commerce Bridge Check

With `shopware/agentic-commerce` installed and UCP enabled for the sales
channel:

1. Open `/.well-known/ai-catalog.json`.
2. Confirm a `Shopware UCP Profile` entry points to `/.well-known/ucp`.
3. If MCP is available for the lane, confirm a `Shopware UCP MCP` entry points
   to `/ucp/mcp`.
4. If native discovery routes are available, confirm entries for `/agents.md`
   and `/llms.txt`.
5. Disable UCP for the sales channel and confirm UCP/MCP entries disappear.

## ARD Conformance

Clone or vendor the ARD spec repository into this plugin checkout:

```bash
git clone https://github.com/ards-project/ard-spec.git tools/ard-spec
```

Run:

```bash
docker compose run --rm qa bin/ard-conformance.sh "$SHOP_URL"
```

This runs both:

- ARD conformance manifest test
- ARD conformance registry test
