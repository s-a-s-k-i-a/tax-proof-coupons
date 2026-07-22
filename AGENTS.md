# Agent instructions

## Source of truth

This GitHub repository is the canonical development source. WordPress.org SVN is a generated release mirror, never the place for independent code changes. Preserve official WordPress.org plugin guidelines and the GPL-compatible distribution.

## Required workflow

1. Read this file, `CLAUDE.md`, the linked development docs, and the issue before editing.
2. Work issue-first. A verified bug needs reproduction evidence, root cause, acceptance criteria, and regression coverage.
3. Keep the requested issue number locked. Put adjacent discoveries in separate issues.
4. Run `composer test`, `composer lint`, PHP syntax checks, and the Playground scenarios.
5. Test cart, Checkout Block, completed order, coupon order-item metadata, and relevant compatibility paths in a real disposable WordPress instance.
6. Merge only after checks pass. A merge is not a release.
7. Release with a numeric tag such as `1.0.6`. The plugin header, `Plugin::VERSION`, both changelogs, `readme.txt` stable tag, Git tag, GitHub release, release ZIP, SVN `trunk`, and SVN tag must match.
8. Verify the public WordPress.org version and downloadable ZIP after deployment.

## Safety and scope

- Do not add telemetry, remote code execution, a custom updater, premium licensing, or promotional admin notices.
- Do not make tax-compliance guarantees. Describe deterministic coupon behavior and tested conditions precisely.
- Do not copy the WP2Amparex EDD/HMAC/customer-bridge release path; this plugin is distributed through WordPress.org.
- Do not publish WordPress.org assets without documented ownership or license provenance.
- Never place SVN credentials in the repository. GitHub Actions uses the protected `wordpress.org` environment and `SVN_USERNAME`/`SVN_PASSWORD` secrets.

## Commands

```bash
composer install
composer test
composer lint
find . -path './vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
./scripts/test-playground.sh
./scripts/build-release.sh 1.0.6
```

See `docs/DEVELOPMENT.md`, `docs/TESTING.md`, and `docs/RELEASING.md` for details.
