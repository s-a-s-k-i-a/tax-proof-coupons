#!/usr/bin/env bash
set -euo pipefail
# Credentials must never appear in shell tracing, even if invoked with bash -x.
set +x

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_slug="taxproof-coupons-for-woocommerce"
svn_url="https://plugins.svn.wordpress.org/$plugin_slug"
dry_run=false

if [[ "$#" -eq 1 && "$1" == '--dry-run' ]]; then
	dry_run=true
elif [[ "$#" -ne 0 ]]; then
	printf 'Usage: %s [--dry-run]\n' "$0" >&2
	exit 2
fi

if [[ "${GITHUB_REF:-}" != 'refs/heads/main' || "${GITHUB_EVENT_NAME:-}" != 'workflow_dispatch' ]]; then
	printf 'Readme publication requires a manual workflow dispatch on main.\n' >&2
	exit 1
fi

for required_command in svn php rsync unzip diff; do
	if ! command -v "$required_command" >/dev/null 2>&1; then
		printf "Required command '%s' was not found.\n" "$required_command" >&2
		exit 127
	fi
done

release_version="$(php "$repository_root/scripts/check-version.php")"
if [[ ! "$release_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	printf 'Readme publication requires a numeric stable version.\n' >&2
	exit 1
fi

publication_temp="$(mktemp -d)"
cleanup_publication_temp() {
	rm -rf "$publication_temp"
}
trap cleanup_publication_temp EXIT

svn_revision="$(svn info --non-interactive --show-item revision "$svn_url")"
plugin_revision="$(svn info --non-interactive -r "$svn_revision" --show-item last-changed-revision "$svn_url")"
"$repository_root/scripts/build-release.sh" "$release_version" >/dev/null
unzip -q "$repository_root/dist/$plugin_slug-$release_version.zip" -d "$publication_temp/build"
build_dir="$publication_temp/build/$plugin_slug"

# Compare the entire deployable tree at one repository snapshot. Only the root
# readme is normalized; a nested readme or any other unexpected file is drift.
for svn_path in "tags/$release_version" trunk; do
	export_dir="$publication_temp/$svn_path"
	mkdir -p "$(dirname "$export_dir")"
	svn export --non-interactive --quiet --ignore-externals -r "$svn_revision" "$svn_url/$svn_path" "$export_dir"
	if [[ ! -f "$export_dir/readme.txt" || -L "$export_dir/readme.txt" ]]; then
		printf 'SVN %s root readme must be a regular file.\n' "$svn_path" >&2
		exit 1
	fi
	stable_version="$(sed -n 's/^Stable tag:[[:space:]]*\([^[:space:]]*\)[[:space:]]*$/\1/p' "$export_dir/readme.txt")"
	if [[ "$stable_version" != "$release_version" ]]; then
		printf 'SVN %s Stable tag does not match %s.\n' "$svn_path" "$release_version" >&2
		exit 1
	fi
	cp "$build_dir/readme.txt" "$export_dir/readme.txt"
	if ! diff -ru "$build_dir" "$export_dir"; then
		printf 'Non-readme plugin drift in SVN %s; refusing metadata publication.\n' "$svn_path" >&2
		exit 1
	fi
done

if "$dry_run"; then
	printf 'Readme preflight passed for %s at SVN revision %s; no changes made.\n' "$release_version" "$svn_revision"
	exit 0
fi

if [[ -z "${SVN_USERNAME:-}" || -z "${SVN_PASSWORD:-}" ]]; then
	printf 'SVN_USERNAME and SVN_PASSWORD are required for publication.\n' >&2
	exit 1
fi

# No root assets, other version tags, or plugin code enter this sparse checkout.
checkout_dir="$publication_temp/checkout"
svn checkout --non-interactive --quiet --ignore-externals -r "$svn_revision" --depth empty "$svn_url" "$checkout_dir"
svn update --non-interactive --quiet --ignore-externals -r "$svn_revision" --depth empty "$checkout_dir/trunk" "$checkout_dir/tags"
svn update --non-interactive --quiet --ignore-externals -r "$svn_revision" --depth empty "$checkout_dir/tags/$release_version"
svn update --non-interactive --quiet --ignore-externals -r "$svn_revision" --depth empty "$checkout_dir/trunk/readme.txt" "$checkout_dir/tags/$release_version/readme.txt"
cp "$build_dir/readme.txt" "$checkout_dir/trunk/readme.txt"
cp "$build_dir/readme.txt" "$checkout_dir/tags/$release_version/readme.txt"

cd "$checkout_dir"
svn status --no-ignore >"$publication_temp/status"
while IFS= read -r status_line; do
	if [[ "$status_line" != 'M       trunk/readme.txt' && "$status_line" != "M       tags/$release_version/readme.txt" ]]; then
		printf 'Unexpected SVN working-copy change; refusing publication.\n' >&2
		exit 1
	fi
done <"$publication_temp/status"

# WordPress.org shares one global repository across all plugins. Changes to this
# plugin subtree invalidate the preflight, unrelated plugins do not.
if [[ "$(svn info --non-interactive --show-item last-changed-revision "$svn_url")" != "$plugin_revision" ]]; then
	printf 'Plugin SVN subtree changed during preflight; rerun against the current repository.\n' >&2
	exit 1
fi

if [[ ! -s "$publication_temp/status" ]]; then
	printf 'WordPress.org readmes already match %s; no commit needed.\n' "$release_version"
	exit 0
fi

# stdin avoids exposing the password in process arguments; no auth is cached.
printf '%s\n' "$SVN_PASSWORD" | svn commit --non-interactive --no-auth-cache \
	--username "$SVN_USERNAME" --password-from-stdin \
	-m "Update approved readme metadata for $release_version (source ${GITHUB_SHA:-local-fixture})" \
	trunk/readme.txt "tags/$release_version/readme.txt"

# Fresh readback confirms the exact published bytes, not just a successful CLI.
for svn_path in "trunk/readme.txt" "tags/$release_version/readme.txt"; do
	svn cat --non-interactive "$svn_url/$svn_path" >"$publication_temp/published-readme.txt"
	cmp "$build_dir/readme.txt" "$publication_temp/published-readme.txt"
done
printf 'Published matching trunk and stable-tag readmes for %s.\n' "$release_version"
