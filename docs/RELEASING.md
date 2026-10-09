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

## Readme-only metadata publication

FAQ and other approved root `readme.txt` corrections do not require a new runtime release. This is the only exception to the numeric-tag workflow above:

1. Review and merge the canonical readme change to `main`, with CI green. Do not change versions or changelog history, move the existing Git tag, or replace its GitHub release ZIP.
2. Manually dispatch **Publish WordPress.org readme** on `main`. The `wordpress.org` environment still requires maintainer approval. This job and runtime deployment share a concurrency group.
3. The publisher validates local version parity and both deployed `Stable tag` values. At one SVN revision, it compares every deployable file against both `tags/<version>` and `trunk`, normalizing only the root `readme.txt`. Missing tags, version mismatch, or any non-readme drift fail closed.
4. A sparse checkout contains only the two root readmes. Unexpected working-copy changes or a changed plugin-subtree revision abort publication. Unrelated plugin commits in WordPress.org's shared SVN repository do not invalidate the preflight. The commit explicitly targets only `trunk/readme.txt` and `tags/<version>/readme.txt`; no root assets, code, or other tags are changed. Credentials are read from step-scoped secrets, passed via stdin, and never cached.
5. Fresh SVN readback verifies both readmes byte-for-byte. The normal full drift check, including `readme.txt`, must pass. Verify the public FAQ and unchanged version on WordPress.org; its directory cache may refresh after SVN.

`./scripts/test-wporg-readme.sh` exercises the publisher against a disposable local SVN repository. `GITHUB_REF=refs/heads/main GITHUB_EVENT_NAME=workflow_dispatch ./scripts/publish-wporg-readme.sh --dry-run` runs the public, read-only preflight without credentials. Dry-run builds an ignored local ZIP but never checks out or commits SVN changes.

After metadata publication, the current canonical Git source and SVN stable tag include the new readme; the historical Git tag and its original GitHub ZIP retain their original documentation. Runtime release files remain immutable. The next numeric release naturally includes the current canonical readme.

## Drift comparison boundary

The scheduled **WordPress.org drift** workflow builds the checked-out canonical source at its declared stable version and compares all its deployable plugin files with WordPress.org SVN `tags/<version>`, including the root readme. Runtime files remain immutable; the explicit metadata-only path above can update that readme. Any changed, missing, or additional file inside the plugin tag still fails the full comparison.

WordPress.org icons, banners, and screenshots live separately in the SVN root `/assets` directory. They are not included in the plugin ZIP or release tag, so asset-only changes intentionally do not count as plugin-code drift. The drift workflow is read-only and never changes either the plugin tag or root assets.
