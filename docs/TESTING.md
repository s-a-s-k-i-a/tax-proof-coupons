# Testing

The test pyramid has four layers:

1. `composer test` verifies pure allocation invariants.
2. `composer lint` checks WordPress coding standards and PHP 7.4 compatibility.
3. `./scripts/test-playground.sh` runs real WooCommerce cart scenarios for 19%, mixed 19%/7%, oversized coupons, tax-inclusive catalog prices, repeated direct totals calculations, and a smoke with the current WordPress.org Advanced Dynamic Pricing build.
4. Browser E2E completes a Checkout Block order and verifies the persisted HPOS order total and hidden coupon-item metadata. The WPML contract blueprint activates only the plugin's existing WCML branch; use current licensed WPML/WCML builds for the final compatibility smoke when available.

For release testing, record WordPress, WooCommerce, PHP, browser, third-party plugin versions, inputs, expected values, actual values, and the resulting order ID.
