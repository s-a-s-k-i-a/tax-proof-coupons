#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if ! command -v svn >/dev/null 2>&1; then
	printf "Required command 'svn' was not found. Install the Subversion client before running the WordPress.org drift comparison.\n" >&2
	exit 127
fi

release_version="${1:-$(php "$repository_root/scripts/check-version.php")}"
plugin_slug="taxproof-coupons-for-woocommerce"
comparison_temp="$(mktemp -d)"

cleanup_comparison_temp() {
	rm -rf "$comparison_temp"
}
trap cleanup_comparison_temp EXIT

"$repository_root/scripts/build-release.sh" "$release_version" >/dev/null
unzip -q "$repository_root/dist/$plugin_slug-$release_version.zip" -d "$comparison_temp/github"
# WordPress.org root assets are maintained separately; plugin-code drift is scoped to this immutable release tag.
svn export --quiet "https://plugins.svn.wordpress.org/$plugin_slug/tags/$release_version" "$comparison_temp/svn/$plugin_slug"

diff -ru "$comparison_temp/github/$plugin_slug" "$comparison_temp/svn/$plugin_slug"
echo "GitHub release $release_version matches the WordPress.org SVN tag."
