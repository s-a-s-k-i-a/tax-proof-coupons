# Changelog

## 1.0.6 — 2026-07-22

- Fix WPML/WCML order totals being recalculated from pre-discount line subtotals, which could produce negative completed-order totals.
- Allocate gross fixed-cart coupons against each discounted line's own tax rate instead of a cart-wide average.
- Reset runtime allocation state for every WooCommerce coupon distribution, including direct repeated totals calculations used by compatibility plugins.
- Use WooCommerce's actual checkout coupon-item hook and persist gross, net, and tax metadata without early order saves.
- Preserve WooCommerce's native calculation when catalog prices already include tax.
- Add automated unit, WooCommerce cart, Checkout Block, HPOS order, and WPML-contract regression coverage.
- Establish GitHub as the canonical source with CI, reproducible release artifacts, and guarded WordPress.org deployment.

## 1.0.5 — 2026-03

- Refactor the plugin into a core service plus isolated WPML and StoreaBill compatibility layers.
- Cap oversized coupons to the discountable gross cart value.
- Persist gross, net, and tax components on order coupon items.

Earlier release notes remain available in `readme.txt`.
