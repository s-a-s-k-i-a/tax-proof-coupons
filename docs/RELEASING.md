# Releasing

## Preconditions

- All issue acceptance criteria and tests are green.
- Version values match in the plugin header, `Plugin::VERSION`, `readme.txt`, and changelogs.
- `Tested up to` reflects a real test, not an assumption.
- The GitHub `wordpress.org` environment has a required maintainer approval and the `SVN_USERNAME` and `SVN_PASSWORD` secrets.
- `./scripts/test-release-contents.sh` passes; the build also audits its staged files before creating the ZIP.

## Release

1. Merge the reviewed pull request to `main`.
2. Create and push a numeric annotated tag, for example `1.0.6`.
3. The release workflow validates, tests, builds, and publishes the GitHub release ZIP.
4. After the protected-environment approval, the same tag contents are deployed to WordPress.org SVN `trunk` and `tags/<version>`.
5. Verify the SVN tag, public plugin page version, and downloaded WordPress.org ZIP against the release manifest.

Never edit SVN independently. If emergency SVN recovery is unavoidable, immediately import the exact committed result back into Git and document the divergence.

## Drift comparison boundary

The scheduled **WordPress.org drift** workflow rebuilds the stable GitHub release and compares its deployable plugin files only with the immutable WordPress.org SVN `tags/<version>` directory. Any changed, missing, or additional file inside that plugin tag fails the comparison.

WordPress.org icons, banners, and screenshots live separately in the SVN root `/assets` directory. They are not included in the plugin ZIP or release tag, so asset-only changes intentionally do not count as plugin-code drift. The drift workflow is read-only and never changes either the plugin tag or root assets.
