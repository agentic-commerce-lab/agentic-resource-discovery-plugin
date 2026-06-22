# ARD Conformance Tooling

The official ARD conformance tool lives in the public ARD spec repository:

https://github.com/ards-project/ard-spec

This plugin does not vendor the tool by default. To run conformance locally,
clone or vendor the spec repository into this directory:

```bash
git clone https://github.com/ards-project/ard-spec.git tools/ard-spec
```

That checkout provides:

```text
tools/ard-spec/conformance/bin/conformance-test
```

Run both manifest and registry checks against a live Shopware base URL:

```bash
docker compose run --rm qa bin/ard-conformance.sh https://example.com
```

The helper runs:

```bash
python3 tools/ard-spec/conformance/bin/conformance-test manifest "$BASE_URL/.well-known/ai-catalog.json"
python3 tools/ard-spec/conformance/bin/conformance-test registry "$BASE_URL/ard"
```

The base URL must point to a running Shopware storefront where this plugin is
installed and active.
