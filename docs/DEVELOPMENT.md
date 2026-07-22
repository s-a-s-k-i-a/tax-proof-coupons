# Development

GitHub `main` is the canonical source. WordPress.org SVN is updated only from a tested numeric Git tag.

Install tooling with `composer install`. Production code must remain compatible with PHP 7.4 and follow WordPress Coding Standards. Do not commit `vendor/`, Playground site state, generated ZIP files, or credentials.

Use one issue per independently reviewable defect or feature. Compatibility reports should identify the exact third-party plugin and version, then reduce the behavior to a WooCommerce contract whenever possible. Proprietary plugins may be represented by a narrow contract shim in automated tests; a real licensed-plugin smoke test is still required before claiming full compatibility.
