# Testing

The test pyramid has four layers:

1. `composer test` verifies pure allocation invariants.
2. `composer lint` checks WordPress coding standards and PHP 7.4 compatibility.
3. `./scripts/test-playground.sh` runs real WooCommerce cart scenarios for 19%, mixed 19%/7%, oversized coupons, tax-inclusive catalog prices, repeated direct totals calculations, and a smoke with the current WordPress.org Advanced Dynamic Pricing build.
4. Browser E2E completes a Checkout Block order and verifies the persisted HPOS order total and hidden coupon-item metadata. The WPML contract blueprint activates only the plugin's existing WCML branch; use current licensed WPML/WCML builds for the final compatibility smoke when available.

For release testing, record WordPress, WooCommerce, PHP, browser, third-party plugin versions, inputs, expected values, actual values, and the resulting order ID.

## Persistent LocalWP release smoke

The dedicated LocalWP site **Tax-proof Coupons for WooCommerce** complements the disposable Playground matrix. Its default WordPress root is `~/Local Sites/tax-proof-coupons-for-woocommerce/app/public`. LocalWP runtime files, database contents, generated products, credentials, and logs stay outside Git.

Start the site in LocalWP, then run:

```bash
./scripts/test-localwp.sh info
./scripts/test-localwp.sh smoke 1.0.7
```

`smoke` installs the exact public WordPress.org release ZIP, activates the current WooCommerce release, resets the dedicated tax/product/coupon fixture, and runs the same base, mixed-rate, oversized, tax-inclusive, and repeated-totals assertions used in Playground. The fixture deletes and recreates WooCommerce tax rates, so never point this runner at a customer, staging, or general-purpose local site. Override the site name only for another explicitly disposable test installation:

```bash
TPC_LOCAL_SITE_NAME='Another disposable site' ./scripts/test-localwp.sh test
```

For the browser layer, add **Tax test product** to the cart, apply `taxproof35`, and verify both Cart Block and Checkout Block show a 35.00 EUR discount, a 4.27 EUR total, and 0.68 EUR VAT. Do not place an order unless persisted order/HPOS behavior is the test target. Final release smoke must use the downloadable WordPress.org ZIP rather than a source-directory symlink.
