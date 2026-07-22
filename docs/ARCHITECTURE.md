# Architecture

`tax-proof-coupons-plugin.php` loads a small service layer after plugins are available.

- `Coupon_Service` owns coupon settings, line-aware gross-to-net allocation, cart display, and order coupon metadata.
- `Gross_Discount_Allocator` is a pure calculation boundary with unit tests.
- `WPML_Integration` corrects a mutated completed-order total from persisted discounted line totals, fees, shipping, and tax. It does not override order totals on every read.
- `StoreaBill_Integration` exposes the persisted gross/net/tax split only through StoreaBill-specific hooks.

WooCommerce remains responsible for eligibility, item ordering, caps, tax calculation, checkout creation, and HPOS persistence. The plugin changes only the net share returned for an opted-in fixed-cart coupon when catalog prices exclude tax.

The bootstrap declares compatibility with WooCommerce HPOS and Cart/Checkout Blocks. WooCommerce 8.8 is the minimum because the per-distribution reset uses the `woocommerce_coupon_get_items_to_apply` lifecycle hook introduced in that release.
