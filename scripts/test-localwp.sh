#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
local_data_root="${HOME}/Library/Application Support/Local"
local_sites_file="${local_data_root}/sites.json"
local_site_name="${TPC_LOCAL_SITE_NAME:-Tax-proof Coupons for WooCommerce}"

fail() {
	printf 'Error: %s\n' "$1" >&2
	exit 1
}

command -v jq >/dev/null 2>&1 || fail 'jq is required to discover the LocalWP site.'
[[ -f "$local_sites_file" ]] || fail "LocalWP site registry not found at $local_sites_file."

local_site_json="$(
	jq -ce --arg site_name "$local_site_name" '
		[ to_entries[] | select(.value.name == $site_name) ]
		| if length == 1 then .[0] else error("expected exactly one matching LocalWP site") end
	' "$local_sites_file"
)" || fail "Could not resolve exactly one LocalWP site named '$local_site_name'."

local_site_id="$(jq -r '.key' <<<"$local_site_json")"
local_site_path_raw="$(jq -r '.value.path' <<<"$local_site_json")"
local_php_version="$(jq -r '.value.services.php.version' <<<"$local_site_json")"
local_site_path="${local_site_path_raw/#\~/${HOME}}/app/public"
local_run_root="${local_data_root}/run/${local_site_id}"
local_php_ini="${local_run_root}/conf/php/php.ini"
local_wp_cli='/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar'

shopt -s nullglob
local_php_candidates=( "${local_data_root}/lightning-services/php-${local_php_version}+"*/bin/darwin-arm64/bin/php )
shopt -u nullglob

[[ ${#local_php_candidates[@]} -eq 1 ]] || fail "Could not resolve one LocalWP PHP ${local_php_version} binary."
local_php="${local_php_candidates[0]}"

[[ -f "$local_php_ini" ]] || fail "LocalWP site '$local_site_name' is not running (missing runtime php.ini)."
[[ -f "$local_wp_cli" ]] || fail 'LocalWP wp-cli.phar was not found.'
[[ -f "${local_site_path}/wp-config.php" ]] || fail "WordPress was not found at ${local_site_path}."

run_wp() {
	"$local_php" -c "$local_php_ini" "$local_wp_cli" --path="$local_site_path" "$@"
}

show_info() {
	printf 'LocalWP site: %s\n' "$local_site_name"
	printf 'Site path: %s\n' "$local_site_path"
	printf 'WordPress: '
	run_wp core version
	printf 'PHP: %s\n' "$local_php_version"
	printf 'Home URL: '
	run_wp option get home
	run_wp plugin list --fields=name,status,version,update --format=table
}

install_release() {
	local version="$1"

	[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail 'Release version must look like 1.0.7.'

	if ! run_wp plugin is-installed woocommerce; then
		run_wp plugin install woocommerce
	fi
	run_wp plugin activate woocommerce
	run_wp plugin install "https://downloads.wordpress.org/plugin/taxproof-coupons-for-woocommerce.${version}.zip" --activate --force
	run_wp transient delete update_plugins >/dev/null || true
}

run_matrix() {
	run_wp plugin is-active woocommerce || fail 'WooCommerce must be active.'
	run_wp plugin is-active taxproof-coupons-for-woocommerce || fail 'Tax-Proof Coupons must be active.'

	# This fixture replaces WooCommerce tax rates and creates test products/coupons.
	# It is intentionally limited to the dedicated LocalWP test site resolved above.
	run_wp eval-file "${repository_root}/tests/playground/setup-store.php"
	run_wp eval-file "${repository_root}/tests/playground/assert-cart-scenarios.php"
}

usage() {
	cat <<'EOF'
Usage: ./scripts/test-localwp.sh <command> [version]

Commands:
  info                 Show the resolved LocalWP runtime and plugin versions.
  install <version>    Install WooCommerce and the exact public wp.org release.
  test                 Reset the dedicated test fixture and run the cart matrix.
  smoke <version>      Install the public release, then run the cart matrix.

Set TPC_LOCAL_SITE_NAME to override the default LocalWP site name.
EOF
}

case "${1:-}" in
	info)
		show_info
		;;
	install)
		[[ $# -eq 2 ]] || fail 'The install command requires a version.'
		install_release "$2"
		;;
	test)
		run_matrix
		;;
	smoke)
		[[ $# -eq 2 ]] || fail 'The smoke command requires a version.'
		install_release "$2"
		run_matrix
		;;
	*)
		usage
		exit 2
		;;
esac
