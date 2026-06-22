#!/usr/bin/env sh
set -eu

if [ "$#" -ne 1 ]; then
    echo "Usage: bin/ard-conformance.sh <base-url>" >&2
    exit 2
fi

BASE_URL="${1%/}"
CONFORMANCE="tools/ard-spec/conformance/bin/conformance-test"

if [ ! -f "$CONFORMANCE" ]; then
    echo "Missing $CONFORMANCE." >&2
    echo "Clone or vendor ards-project/ard-spec into tools/ard-spec first." >&2
    exit 2
fi

python3 tools/ard-spec/conformance/bin/conformance-test manifest "$BASE_URL/.well-known/ai-catalog.json"
python3 tools/ard-spec/conformance/bin/conformance-test registry "$BASE_URL/ard"
