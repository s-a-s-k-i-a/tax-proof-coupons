# Tax‑Proof Coupons for WooCommerce

**Contributors:** Jyria

**Donate link:** https://isla-stud.io/donate/

**Tags:** woocommerce, coupon, tax, discount

**Requires at least:** 6.5

**Requires PHP:** 7.4

**Requires WooCommerce:** 8.8

**Tested with WooCommerce:** 10.9.4

**Tested up to:** 7.0

**Stable tag:** 1.0.7

**License:** GPLv2 or later

**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Treat selected fixed-cart coupons as gross promotional values and convert them into the net discounts WooCommerce expects.

## What it does

Tax-Proof Coupons adds an **Apply coupon after tax** option to fixed-cart coupons. When enabled, a coupon entered as 35.00 EUR reduces eligible products by 35.00 EUR including tax, within WooCommerce currency precision. Each coupon share is converted using the tax rate of the product line it discounts, so mixed-rate carts do not rely on a cart-wide average.

The plugin does not choose tax rates, change product prices, discount otherwise ineligible amounts, or provide tax or legal advice. The effective discount is capped at the gross value of eligible product lines.

## Features

- Adds **Apply coupon after tax** checkbox to coupon settings.
- Converts each gross coupon share into the net discount WooCommerce expects using the current line's tax rate.
- Keeps the intended gross amount stable within WooCommerce currency precision and the eligible product value.
- Caps oversized coupons to the actually discountable gross cart value.
- **StoreaBill/Germanized Pro Integration** via a dedicated compatibility layer.
- **WPML/WCML compatibility path** for persisting the valid WooCommerce checkout total.
- **Enhanced Admin Display** showing the persisted gross, net, and tax split.

Only fixed-cart coupons with the option enabled are changed. Other coupon types and disabled coupons retain native WooCommerce behavior.

## Installation

1. Extract the `taxproof-coupons-for-woocommerce` folder into `/wp-content/plugins/`.
2. Activate **Tax‑Proof Coupons for WooCommerce** via **Plugins** in WordPress.
3. Edit or create a **Fixed Cart** coupon in WooCommerce and check **Apply coupon after tax**.

## Development

GitHub is the canonical source; WordPress.org SVN is a generated release mirror. See the [agent workflow](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/AGENTS.md), [testing guide](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/docs/TESTING.md), and [release guide](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/docs/RELEASING.md).

Version 1.0.7 is tested with WordPress 7.0, WooCommerce 10.9.4, Checkout Block, and HPOS. Automated coverage also exercises PHP 7.4–8.4, mixed tax rates, oversized coupons, tax-inclusive catalog prices, repeated totals calculations, Advanced Dynamic Pricing, and a narrow WPML/WCML contract. A current licensed WPML/WCML build is still required before claiming unrestricted compatibility with a specific commercial release.

## Changelog

### 1.0.7

**For store owners**

- Added a plain-language explanation, a worked example, explicit limits, and upgrade guidance.
- Corrected the installation folder and historical release dates.
- No coupon calculation or setting changed in this release.

**For developers**

- Separated user-visible behavior from implementation details and qualified compatibility claims.
- Documented the exact WooCommerce calculation boundary without tax-compliance guarantees.

### 1.0.6

**For store owners**

- Fixed negative saved order totals in the WPML/WCML path.
- Fixed mixed-tax carts, oversized coupons, and unstable repeated calculations.

**For developers**

- Added per-line gross allocation and per-distribution state reset.
- Corrected checkout coupon-item persistence for HPOS and Checkout Block.
- Added unit, Playground cart, Advanced Dynamic Pricing, and browser E2E regressions.

### 1.0.5

- Refactored the plugin into a lean core service with isolated StoreaBill and WPML integrations.
- Fixed carts where the configured gross coupon amount exceeds the discountable order value.
- Removed release-time debug logging and total-adjustment hacks from the wp.org build.
- Persist the gross/net/tax split on order coupon items for more reliable invoice generation.

### 1.0.4

- **Verbesserte Präzision**: Neue präzise Berechnungsmethode für exakte Steuerumrechnungen
- **Erweiterte Anzeige-Funktionen**: Verbesserte Coupon-Anzeige im Warenkorb und Checkout
- **StoreaBill/Germanized Pro Integration**: Vollständige Kompatibilität mit Germanized Pro für Rechnungsgenerierung
- **Admin-Verbesserungen**: Detaillierte Anzeige von Netto- und Bruttobeträgen in der WooCommerce Admin-Oberfläche
- **Erweiterte Metadaten-Speicherung**: Präzise Speicherung von Coupon-Beträgen mit hoher Genauigkeit
- **Hook-Integration**: Neue Hooks für bessere Integration mit WooCommerce und Drittanbieter-Plugins
- **Performance-Optimierungen**: Verbesserte Berechnungslogik für komplexe Steuerszenarien

### 1.0.3

- Ensuring unique namespace
- Added Requires plugins plugin header

### 1.0.2

- Fixed class and method visibility issues.
- Ensured coupon applies only once per order.

### 1.0.1

- Initial addition of gross-to-net conversion logic for fixed-cart coupons.

## Contributing

Pull requests and issues welcome on [GitHub](https://github.com/s-a-s-k-i-a/tax-proof-coupons).
