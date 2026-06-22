#!/usr/bin/env sh
set -eu

PLUGIN_NAME="SwagAgenticResourceDiscovery"
ZIP_NAME="SwagAgenticResourceDiscovery.zip"
DIST_DIR="dist"
WORK_DIR="$DIST_DIR/package"
ZIP_PATH="$DIST_DIR/$ZIP_NAME"

rm -rf "$WORK_DIR" "$ZIP_PATH"
mkdir -p "$WORK_DIR"
mkdir "$WORK_DIR/$PLUGIN_NAME"

cp composer.json "$WORK_DIR/$PLUGIN_NAME/"
cp composer.lock "$WORK_DIR/$PLUGIN_NAME/"
cp LICENSE "$WORK_DIR/$PLUGIN_NAME/"
cp README.md "$WORK_DIR/$PLUGIN_NAME/"
cp .shopware-extension.yml "$WORK_DIR/$PLUGIN_NAME/"
cp -R src "$WORK_DIR/$PLUGIN_NAME/"

cd "$WORK_DIR"

zip -rq "../$ZIP_NAME" "SwagAgenticResourceDiscovery/" \
  --exclude=.github/* \
  --exclude=.tools/* \
  --exclude=dist/* \
  --exclude=docs/* \
  --exclude=tests/* \
  --exclude=tools/* \
  --exclude=Dockerfile \
  --exclude=docker-compose.yml \
  --exclude=SwagAgenticResourceDiscovery/.github/* \
  --exclude=SwagAgenticResourceDiscovery/.tools/* \
  --exclude=SwagAgenticResourceDiscovery/dist/* \
  --exclude=SwagAgenticResourceDiscovery/docs/* \
  --exclude=SwagAgenticResourceDiscovery/tests/* \
  --exclude=SwagAgenticResourceDiscovery/tools/* \
  --exclude=SwagAgenticResourceDiscovery/Dockerfile \
  --exclude=SwagAgenticResourceDiscovery/docker-compose.yml

cd ../..
rm -rf "$WORK_DIR"

echo "$ZIP_PATH"
