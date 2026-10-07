#!/usr/bin/env bash
# Builds dist/mindio-magic-ai-<version>.zip from the working tree.
# Ships vendor-prefixed/ (the namespaced Composer packages), build/, and the
# sources in assets/src with package.json and composer.json, as WordPress.org asks.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
plugin_dir="$(cd "${script_dir}/.." && pwd)"
version="$(sed -n "s/^define( 'MINDIO_MAGIC_AI_VERSION', '\\([^']*\\)' );$/\\1/p" "${plugin_dir}/mindio-magic-ai.php")"

if [[ -z "${version}" ]]; then
	echo "Could not determine MINDIO_MAGIC_AI_VERSION." >&2
	exit 1
fi
stable="$(sed -n 's/^Stable tag: *//p' "${plugin_dir}/readme.txt")"
if [[ "${stable}" != "${version}" ]]; then
	echo "readme.txt Stable tag (${stable}) does not match the plugin version (${version})." >&2
	exit 1
fi
for required in vendor-prefixed/autoload.php build/admin.js build/admin.asset.php build/drawer.js languages/mindio-magic-ai.pot; do
	if [[ ! -f "${plugin_dir}/${required}" ]]; then
		echo "Missing ${required}. Run composer prefix, npm run build, and bin/i18n.sh first." >&2
		exit 1
	fi
done

release_dir="${plugin_dir}/dist"
archive="${release_dir}/mindio-magic-ai-${version}.zip"
staging_root="$(mktemp -d)"
trap 'rm -rf "${staging_root}"' EXIT

mkdir -p "${release_dir}" "${staging_root}/mindio-magic-ai"
rsync -a --delete --exclude-from="${plugin_dir}/.distignore" "${plugin_dir}/" "${staging_root}/mindio-magic-ai/"

rm -f "${archive}"
(
	cd "${staging_root}"
	zip -q -r -X "${archive}" mindio-magic-ai
)

echo "Built ${archive} ($(du -h "${archive}" | cut -f1))"
