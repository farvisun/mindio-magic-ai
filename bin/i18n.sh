#!/usr/bin/env bash
# Regenerates the POT, merges it into every PO, and compiles MO and script JSON files.
# Needs WP-CLI (`wp`, or set WP=...) and gettext (msgmerge, msgfmt). Run `npm run build` first.
set -euo pipefail
cd "$(dirname "$0")/.."
WP="${WP:-wp}"

$WP i18n make-pot . languages/mindio-magic-ai.pot \
	--slug=mindio-magic-ai --domain=mindio-magic-ai \
	--exclude=node_modules,vendor,vendor-prefixed,build,tests,bin \
	--headers='{"Report-Msgid-Bugs-To":"https://github.com/farvisun/mindio-magic-ai/issues"}'

map="$(mktemp)"
trap 'rm -f "$map"' EXIT
php bin/i18n-map.php > "$map"
rm -f languages/*.json

for po in languages/*.po; do
	msgmerge --quiet --update --backup=none --no-fuzzy-matching "$po" languages/mindio-magic-ai.pot
	msgfmt -c --statistics -o "${po%.po}.mo" "$po"
done
$WP i18n make-json languages --no-purge --use-map="$map"
php bin/i18n-fix-json.php
