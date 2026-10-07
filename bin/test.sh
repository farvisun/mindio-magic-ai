#!/usr/bin/env bash
# Runs the integration suites against a real WordPress install with the plugin active.
# Usage: WP_PATH=/path/to/wordpress bin/test.sh [suite ...]
# Extra PHP flags (for example a DB_HOST override) can be passed in PHP_ARGS.
set -euo pipefail
cd "$(dirname "$0")/.."

: "${WP_PATH:?Set WP_PATH to a WordPress install with Mindio Magic AI active.}"
export WP_PATH
export MINDIO_MOCK_DIR="${MINDIO_MOCK_DIR:-$(mktemp -d)}"
read -r -a php_args <<< "${PHP_ARGS:-}"

PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8766 tests/mock/provider.php > "${MINDIO_MOCK_DIR}/server.log" 2>&1 &
server=$!
trap 'pkill -P "${server}" 2>/dev/null || true; kill "${server}" 2>/dev/null || true' EXIT
for _ in $(seq 1 50); do
	if php -r 'exit(@fsockopen("127.0.0.1", 8766) ? 0 : 1);'; then
		break
	fi
	sleep 0.1
done

suites=("$@")
if [[ ${#suites[@]} -eq 0 ]]; then
	suites=(assistant-flow security openai-flow woocommerce)
fi
status=0
for suite in "${suites[@]}"; do
	php "${php_args[@]}" tests/integration/"${suite}".php || status=1
done
exit "${status}"
