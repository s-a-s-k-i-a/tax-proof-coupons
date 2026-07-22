#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
playground_version="3.1.46"
plugin_mount="$repository_root:/wordpress/wp-content/plugins/taxproof-coupons-for-woocommerce"
shim_mount="$repository_root/tests/playground/wpml-contract-shim.php:/wordpress/wp-content/plugins/wpml-contract-shim.php"

npx --yes "@wp-playground/cli@$playground_version" run-blueprint \
	--mount="$plugin_mount" \
	--blueprint="$repository_root/.playground/blueprint.json"

npx --yes "@wp-playground/cli@$playground_version" run-blueprint \
	--mount="$plugin_mount" \
	--mount="$shim_mount" \
	--blueprint="$repository_root/.playground/blueprint-wpml-contract.json"

npx --yes "@wp-playground/cli@$playground_version" run-blueprint \
	--mount="$plugin_mount" \
	--blueprint="$repository_root/.playground/blueprint-adp.json"
