# Changelog

## 1.0.8 — 2026-07-22

### For store owners

- Correct StoreaBill's aggregate gross invoice discount when its own line rounding differs by one cent from the persisted Tax-Proof coupon amount.
- Keep the payable invoice total, tax totals, coupon settings, and native behavior for unrelated coupons unchanged.

### For developers

- Resolve StoreaBill 4.3.4's nested invoice-to-order-wrapper-to-WooCommerce-order contract before reading coupon metadata.
- Add a contract regression for nested wrappers and unsupported subjects.
- Verify real synchronized and finalized PDFs for 19%, mixed 19%/7%, both tax lines remaining, tax-inclusive catalog prices, and a fully discounted zero-total order.

## 1.0.7 — 2026-07-22

### For store owners

- Explain in plain language when an after-tax coupon applies, with a concrete gross-discount example.
- Document the limits: only enabled fixed-cart coupons are affected, the discount cannot exceed eligible product lines, and results follow WooCommerce currency precision.
- Clarify that updating from 1.0.6 requires no settings changes and does not change coupon calculations.
- Correct the installation folder name and the historical release dates shown for 1.0.3 and 1.0.4.

### For developers

- Separate user-visible behavior from implementation details and compatibility claims.
- Document the supported WooCommerce calculation boundary without making tax-compliance or unrestricted third-party compatibility guarantees.
- No production PHP behavior changed in this release.

## 1.0.6 — 2026-07-22

### For store owners

- Prevent negative saved order totals in the WPML/WCML compatibility path. The order now keeps the valid total calculated at checkout.
- Apply one gross coupon predictably across carts containing products with different tax rates.
- Keep the result stable when pricing or compatibility extensions ask WooCommerce to calculate the same cart more than once.
- Cap a coupon at the eligible product value instead of allowing an oversized coupon to distort the order total.

### For developers

- Allocate the gross fixed-cart coupon against each discounted line's own tax rate instead of using a cart-wide average.
- Reset allocation state for every WooCommerce coupon distribution, including repeated direct totals calculations.
- Use WooCommerce's supported checkout coupon-item hook and persist gross, net, and tax metadata without an early order save.
- Preserve WooCommerce's native calculation when catalog prices already include tax.
- Add unit, WooCommerce cart, Checkout Block, HPOS order, Advanced Dynamic Pricing, and WPML-contract regression coverage.
- Establish GitHub as the canonical source with CI, reproducible artifacts, and a guarded WordPress.org deployment.

## 1.0.5 — 2026-03-07

- Refactor the plugin into a core service plus isolated WPML and StoreaBill compatibility layers.
- Cap oversized coupons to the discountable gross cart value.
- Persist gross, net, and tax components on order coupon items.

Earlier release notes remain available in `readme.txt`.
