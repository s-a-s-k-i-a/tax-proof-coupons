#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_version="$(php "$repository_root/scripts/check-version.php")"
plugin_slug="taxproof-coupons-for-woocommerce"
test_root="$(mktemp -d)"
svn_root="$test_root/svn-root"
fake_bin="$test_root/bin"

cleanup_test_root() {
	rm -rf "$test_root"
}
trap cleanup_test_root EXIT

mkdir -p "$fake_bin" "$svn_root/assets" "$svn_root/tags/$release_version"

missing_bin="$test_root/no-svn-bin"
mkdir -p "$missing_bin"
ln -s "$(command -v dirname)" "$missing_bin/dirname"

if PATH="$missing_bin" "$BASH" "$repository_root/scripts/verify-svn-sync.sh" "$release_version" >"$test_root/missing-svn.log" 2>&1; then
	printf 'Drift comparison unexpectedly passed without an SVN client.\n' >&2
	exit 1
fi

if ! grep -Fq "Required command 'svn' was not found." "$test_root/missing-svn.log"; then
	printf 'Missing SVN client did not produce the expected prerequisite message.\n' >&2
	cat "$test_root/missing-svn.log" >&2
	exit 1
fi

"$repository_root/scripts/build-release.sh" "$release_version" >/dev/null
unzip -q "$repository_root/dist/$plugin_slug-$release_version.zip" -d "$test_root/release"
rsync -a "$test_root/release/$plugin_slug/" "$svn_root/tags/$release_version/"

printf 'first asset revision\n' >"$svn_root/assets/banner-772x250.png"

cat >"$fake_bin/svn" <<'FAKE_SVN'
#!/usr/bin/env bash
set -euo pipefail

expected_url="https://plugins.svn.wordpress.org/$TPC_TEST_PLUGIN_SLUG/tags/$TPC_TEST_RELEASE_VERSION"

if [[ "$#" -ne 4 || "$1" != 'export' || "$2" != '--quiet' || "$3" != "$expected_url" ]]; then
	printf 'Unexpected SVN invocation: %q ' "$@" >&2
	printf '\n' >&2
	exit 2
fi

mkdir -p "$(dirname "$4")"
cp -R "$TPC_TEST_SVN_ROOT/tags/$TPC_TEST_RELEASE_VERSION" "$4"
FAKE_SVN
chmod +x "$fake_bin/svn"

run_comparison() {
	PATH="$fake_bin:$PATH" \
		TPC_TEST_PLUGIN_SLUG="$plugin_slug" \
		TPC_TEST_RELEASE_VERSION="$release_version" \
		TPC_TEST_SVN_ROOT="$svn_root" \
		"$repository_root/scripts/verify-svn-sync.sh" "$release_version"
}

run_comparison >"$test_root/initial.log"

# Root assets live alongside tags in SVN but are not deployable plugin code.
printf 'second asset revision\n' >"$svn_root/assets/banner-772x250.png"
run_comparison >"$test_root/asset-change.log"

printf '\nSynthetic plugin tag drift.\n' >>"$svn_root/tags/$release_version/readme.txt"
if run_comparison >"$test_root/tag-drift.log" 2>&1; then
	printf 'Drift comparison accepted a synthetic change inside the plugin tag.\n' >&2
	exit 1
fi

if ! grep -Fq 'Synthetic plugin tag drift.' "$test_root/tag-drift.log"; then
	printf 'Plugin tag drift failed without exposing the changed content.\n' >&2
	cat "$test_root/tag-drift.log" >&2
	exit 1
fi

printf 'SVN prerequisite, asset boundary, and plugin tag drift regressions passed.\n'
