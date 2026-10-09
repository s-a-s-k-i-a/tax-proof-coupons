# Development

GitHub `main` is the canonical source. Runtime releases reach WordPress.org SVN only from a tested numeric Git tag. Approved root `readme.txt` metadata updates may use the separate protected, manual publisher described in `RELEASING.md`; runtime files, version tags, historical GitHub release ZIPs, and WordPress.org assets remain unchanged.

Install tooling with `composer install`. Production code must remain compatible with PHP 7.4 and follow WordPress Coding Standards. Do not commit `vendor/`, Playground site state, generated ZIP files, or credentials.

Use one issue per independently reviewable defect or feature. Compatibility reports should identify the exact third-party plugin and version, then reduce the behavior to a WooCommerce contract whenever possible. Proprietary plugins may be represented by a narrow contract shim in automated tests; a real licensed-plugin smoke test is still required before claiming full compatibility.
