#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_version="$(php "$repository_root/scripts/check-version.php")"
plugin_slug="taxproof-coupons-for-woocommerce"
real_svn="$(command -v svn)"
test_root="$(mktemp -d)"

cleanup_test_root() {
	rm -rf "$test_root"
}
trap cleanup_test_root EXIT

"$repository_root/scripts/build-release.sh" >/dev/null
unzip -q "$repository_root/dist/$plugin_slug-$release_version.zip" -d "$test_root/build"
mkdir -p "$test_root/bin"

# Keep production URLs fixed. The fixture-only wrapper redirects them to a real
# disposable SVN repository and records arguments (never password stdin).
cat >"$test_root/bin/svn" <<'FIXTURE_SVN'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$@" >>"$TPC_TEST_CASE/arguments.log"
if [[ "$1" == 'info' && " $* " == *' --show-item last-changed-revision '* && " $* " != *' -r '* ]]; then
	if [[ -f "$TPC_TEST_CASE/change-on-info" ]]; then
		rm "$TPC_TEST_CASE/change-on-info"
		"$TPC_TEST_REAL_SVN" mkdir --quiet "$TPC_TEST_SVN_URL/assets/concurrent" -m 'Fixture concurrent edit'
	elif [[ -f "$TPC_TEST_CASE/unrelated-on-info" ]]; then
		rm "$TPC_TEST_CASE/unrelated-on-info"
		"$TPC_TEST_REAL_SVN" mkdir --quiet "$TPC_TEST_SVN_ROOT/other-plugin" -m 'Unrelated plugin edit'
	fi
fi
rewritten=()
for argument in "$@"; do
	rewritten+=("${argument/https:\/\/plugins.svn.wordpress.org\/taxproof-coupons-for-woocommerce/$TPC_TEST_SVN_URL}")
done
exec "$TPC_TEST_REAL_SVN" "${rewritten[@]}"
FIXTURE_SVN
chmod +x "$test_root/bin/svn"

prepare_case() {
	case_dir="$test_root/$1"
	mkdir -p "$case_dir/import/trunk" "$case_dir/import/tags/$release_version" "$case_dir/import/assets"
	cp -R "$test_root/build/$plugin_slug/." "$case_dir/import/trunk/"
	cp -R "$test_root/build/$plugin_slug/." "$case_dir/import/tags/$release_version/"
	printf '\nPrevious FAQ metadata.\n' >>"$case_dir/import/trunk/readme.txt"
	printf '\nPrevious FAQ metadata.\n' >>"$case_dir/import/tags/$release_version/readme.txt"
	printf 'Existing asset must remain untouched.\n' >"$case_dir/import/assets/banner-772x250.png"
}

import_case() {
	svnadmin create "$case_dir/repository"
	case_url="file://$case_dir/repository/plugin"
	"$real_svn" import --quiet "$case_dir/import" "$case_url" -m 'Fixture baseline'
	before_revision="$(svnlook youngest "$case_dir/repository")"
}

run_publisher() {
	PATH="$test_root/bin:$PATH" \
		GITHUB_REF="${test_ref:-refs/heads/main}" \
		GITHUB_EVENT_NAME="${test_event:-workflow_dispatch}" \
		SVN_USERNAME="${test_username-fixture-maintainer}" SVN_PASSWORD="${test_password-fixture-secret-sentinel}" \
		TPC_TEST_CASE="$case_dir" TPC_TEST_REAL_SVN="$real_svn" TPC_TEST_SVN_URL="$case_url" \
		TPC_TEST_SVN_ROOT="file://$case_dir/repository" \
		"$repository_root/scripts/publish-wporg-readme.sh" "$@"
}

expect_rejection() {
	if run_publisher >"$case_dir/result.log" 2>&1; then
		printf 'Publisher incorrectly accepted case %s.\n' "$case_dir" >&2
		exit 1
	fi
	if ! grep -Fq "$1" "$case_dir/result.log"; then
		cat "$case_dir/result.log" >&2
		printf 'Expected rejection reason missing: %s\n' "$1" >&2
		exit 1
	fi
	if [[ "$(svnlook youngest "$case_dir/repository")" != "$before_revision" ]]; then
		printf 'Rejected publication changed SVN.\n' >&2
		exit 1
	fi
}

prepare_case success
import_case
run_publisher >"$case_dir/result.log" 2>&1
after_revision="$(svnlook youngest "$case_dir/repository")"
if [[ "$after_revision" != "$((before_revision + 1))" ]]; then
	printf 'Successful publication did not create exactly one SVN revision.\n' >&2
	exit 1
fi
expected_paths="$(printf 'U   plugin/tags/%s/readme.txt\nU   plugin/trunk/readme.txt' "$release_version")"
if [[ "$(svnlook changed -r "$after_revision" "$case_dir/repository")" != "$expected_paths" ]]; then
	printf 'Publication changed paths outside the two root readmes.\n' >&2
	exit 1
fi
for svn_path in trunk "tags/$release_version"; do
	"$real_svn" cat "$case_url/$svn_path/readme.txt" >"$case_dir/readback.txt"
	cmp "$repository_root/readme.txt" "$case_dir/readback.txt"
done
if grep -Fq 'fixture-secret-sentinel' "$case_dir/arguments.log" "$case_dir/result.log"; then
	printf 'Password appeared in process arguments or output.\n' >&2
	exit 1
fi
grep -Fxq -- '--password-from-stdin' "$case_dir/arguments.log"
grep -Fxq -- '--no-auth-cache' "$case_dir/arguments.log"
run_publisher >"$case_dir/no-op.log" 2>&1
grep -Fq 'no commit needed' "$case_dir/no-op.log"
[[ "$(svnlook youngest "$case_dir/repository")" == "$after_revision" ]]

prepare_case dry-run
import_case
test_password='' run_publisher --dry-run >"$case_dir/result.log" 2>&1
[[ "$(svnlook youngest "$case_dir/repository")" == "$before_revision" ]]
if grep -Fxq 'commit' "$case_dir/arguments.log"; then
	printf 'Dry-run attempted an SVN commit.\n' >&2
	exit 1
fi

prepare_case wrong-ref
import_case
test_ref='refs/heads/topic' expect_rejection 'manual workflow dispatch on main'

prepare_case wrong-event
import_case
test_event='push' expect_rejection 'manual workflow dispatch on main'

prepare_case missing-credentials
import_case
test_password='' expect_rejection 'SVN_USERNAME and SVN_PASSWORD are required'

prepare_case missing-username
import_case
test_username='' expect_rejection 'SVN_USERNAME and SVN_PASSWORD are required'

for drift_path in trunk "tags/$release_version"; do
	prepare_case "code-drift-${drift_path//\//-}"
	printf '\nSynthetic runtime drift.\n' >>"$case_dir/import/$drift_path/tax-proof-coupons-plugin.php"
	import_case
	expect_rejection 'Non-readme plugin drift'
done

prepare_case nested-readme
mkdir -p "$case_dir/import/trunk/unexpected"
printf 'This nested readme must not be ignored.\n' >"$case_dir/import/trunk/unexpected/readme.txt"
import_case
expect_rejection 'Non-readme plugin drift'

prepare_case readme-symlink
mv "$case_dir/import/trunk/readme.txt" "$case_dir/import/trunk/readme-link-target.txt"
ln -s readme-link-target.txt "$case_dir/import/trunk/readme.txt"
import_case
expect_rejection 'root readme must be a regular file'

for mismatch_path in trunk "tags/$release_version"; do
	prepare_case "version-mismatch-${mismatch_path//\//-}"
	sed "s/^Stable tag: .*/Stable tag: 99.0.0/" "$case_dir/import/$mismatch_path/readme.txt" >"$case_dir/wrong-version.txt"
	cp "$case_dir/wrong-version.txt" "$case_dir/import/$mismatch_path/readme.txt"
	import_case
	expect_rejection 'Stable tag does not match'
done

prepare_case missing-tag
mv "$case_dir/import/tags/$release_version" "$case_dir/import/tags/old-version"
import_case
expect_rejection 'svn:'

prepare_case concurrent-edit
import_case
touch "$case_dir/change-on-info"
if run_publisher >"$case_dir/result.log" 2>&1; then
	printf 'Publisher accepted a concurrent SVN change.\n' >&2
	exit 1
fi
grep -Fq 'Plugin SVN subtree changed during preflight' "$case_dir/result.log"
[[ "$(svnlook youngest "$case_dir/repository")" == "$((before_revision + 1))" ]]
[[ "$(svnlook changed "$case_dir/repository")" == 'A   plugin/assets/concurrent/' ]]
if grep -Fxq 'commit' "$case_dir/arguments.log"; then
	printf 'Concurrent SVN changes did not prevent the commit attempt.\n' >&2
	exit 1
fi

prepare_case unrelated-plugin-edit
import_case
touch "$case_dir/unrelated-on-info"
run_publisher >"$case_dir/result.log" 2>&1
[[ "$(svnlook youngest "$case_dir/repository")" == "$((before_revision + 2))" ]]
[[ "$(svnlook changed "$case_dir/repository")" == "$expected_paths" ]]

printf 'Readme publication: exact two-path commit, no-op, dry-run, gates, credentials, versions, missing tag, complete drift and concurrency regressions passed.\n'
