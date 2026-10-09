# Testing

The test pyramid has four layers:

1. `composer test` verifies pure allocation invariants.
2. `composer lint` checks WordPress coding standards and PHP 7.4 compatibility.
3. `./scripts/test-playground.sh` runs real WooCommerce cart scenarios for 19%, mixed 19%/7%, oversized coupons, tax-inclusive catalog prices, repeated direct totals calculations, and a smoke with the current WordPress.org Advanced Dynamic Pricing build.
4. Browser E2E completes a Checkout Block order and verifies the persisted HPOS order total and hidden coupon-item metadata. The WPML contract blueprint activates only the plugin's existing WCML branch; use current licensed WPML/WCML builds for the final compatibility smoke when available.

For release testing, record WordPress, WooCommerce, PHP, browser, third-party plugin versions, inputs, expected values, actual values, and the resulting order ID.

## WordPress 7.1 metadata verification — 9 October 2026

For issue [#31](https://github.com/s-a-s-k-i-a/tax-proof-coupons/issues/31), plugin 1.0.9 passed all three existing Playground blueprints with both `preferredVersions.wp` and CLI `--wp` explicitly set to `7.1`, using `@wp-playground/cli@3.1.46`. A final WP-CLI step asserted the actual WordPress 7.1.x runtime and completion of all four cart scenarios, then persisted a JSON receipt to a separate scratch mount; those receipts were read back outside Playground.

The actual runtime was **WordPress 7.1.3, PHP 8.3.32, WooCommerce 11.2.0** in all three runs. The WPML-contract run recorded `WCML_VERSION=contract-test`; the Advanced Dynamic Pricing smoke recorded the active public plugin version **4.14.0** with its default configuration, not a custom pricing-rule matrix.

Each blueprint passed the existing gross-discount/payable-total expectations: base 35.00/4.27 EUR, mixed 19%/7% rates 50.00/79.70 EUR, oversized tax-inclusive coupon 46.60/0.00 EUR, and tax-inclusive catalog 35.00/4.27 EUR. Each scenario also passed its existing direct repeated-totals assertions.

This cart/runtime verification supports `Tested up to: 7.1`; it does not claim a new browser checkout/order/HPOS test or licensed WPML/WCML/StoreaBill validation on WordPress 7.1. The historical 1.0.9 compatibility paragraph and licensed-plugin receipts remain unchanged. Only disposable Playground sites were used; the persistent LocalWP installation was not modified.

## Persistent LocalWP release smoke

The dedicated LocalWP site **Tax-proof Coupons for WooCommerce** complements the disposable Playground matrix. Its default WordPress root is `~/Local Sites/tax-proof-coupons-for-woocommerce/app/public`. LocalWP runtime files, database contents, generated products, credentials, and logs stay outside Git.

Start the site in LocalWP, then run:

```bash
./scripts/test-localwp.sh info
./scripts/test-localwp.sh smoke 1.0.9
```

`smoke` installs the exact public WordPress.org release ZIP, activates the current WooCommerce release, resets the dedicated tax/product/coupon fixture, and runs the same base, mixed-rate, oversized, tax-inclusive, and repeated-totals assertions used in Playground. The fixture deletes and recreates WooCommerce tax rates, so never point this runner at a customer, staging, or general-purpose local site. Override the site name only for another explicitly disposable test installation:

```bash
TPC_LOCAL_SITE_NAME='Another disposable site' ./scripts/test-localwp.sh test
```

For the browser layer, add **Tax test product** to the cart, apply `taxproof35`, and verify both Cart Block and Checkout Block show a 35.00 EUR discount, a 4.27 EUR total, and 0.68 EUR VAT. Do not place an order unless persisted order/HPOS behavior is the test target. Final release smoke must use the downloadable WordPress.org ZIP rather than a source-directory symlink.

## Licensed StoreaBill compatibility smoke

StoreaBill is bundled with the proprietary Germanized Pro plugin and is therefore not committed to this repository or installed in public CI. Before a release that changes order coupon metadata or invoice integration, activate the licensed builds on the dedicated LocalWP site and record their exact versions.

The 1.0.8 compatibility smoke used Germanized 4.0.10 and Germanized Pro/StoreaBill 4.3.4. It synchronized and finalized PDF invoices for a 19% cart, mixed 19%/7% carts where one or both tax lines remained payable, tax-inclusive catalog prices, and an oversized coupon producing a zero-total order. For every invoice, compare the WooCommerce and StoreaBill payable total and tax total, the persisted Tax-Proof gross coupon metadata and StoreaBill aggregate discount, and confirm that the finalized document stream is a readable PDF.
