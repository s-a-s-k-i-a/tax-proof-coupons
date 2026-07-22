# Tax‑Proof Coupons for WooCommerce

**Contributors:** Jyria

**Donate link:** https://isla-stud.io/donate/

**Tags:** woocommerce, coupon, tax, discount

**Requires at least:** 6.5

**Requires PHP:** 7.4

**Requires WooCommerce:** 8.8

**Tested with WooCommerce:** 10.9.4

**Tested up to:** 7.0

**Stable tag:** 1.0.6

**License:** GPLv2 or later

**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Always apply fixed-value cart coupons after tax, no matter the VAT rate or customer location.

## Features

- Adds **Apply coupon after tax** checkbox to coupon settings.
- Converts each gross coupon share into the net discount WooCommerce expects using the current line's tax rate.
- Keeps the intended gross amount stable within WooCommerce currency precision and the eligible cart value.
- Caps oversized coupons to the actually discountable gross cart value.
- **StoreaBill/Germanized Pro Integration** via a dedicated compatibility layer.
- **WPML/WCML Compatibility** via an isolated order-total correction layer.
- **Enhanced Admin Display** showing the persisted gross, net, and tax split.

## Installation

1. Extract the `tax-proof-coupons` folder into `/wp-content/plugins/`.
2. Activate **Tax‑Proof Coupons for WooCommerce** via **Plugins** in WordPress.
3. Edit or create a **Fixed Cart** coupon in WooCommerce and check **Apply coupon after tax**.

## Development

GitHub is the canonical source; WordPress.org SVN is a generated release mirror. See the [agent workflow](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/AGENTS.md), [testing guide](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/docs/TESTING.md), and [release guide](https://github.com/s-a-s-k-i-a/tax-proof-coupons/blob/main/docs/RELEASING.md).

## Changelog

### 1.0.6

- Fix negative WPML/WCML completed-order totals.
- Support mixed tax rates with per-line gross allocation.
- Stabilize repeated direct WooCommerce totals calculations.
- Correct checkout coupon-item persistence for HPOS and Checkout Block.
- Add unit, Playground cart, and browser E2E regressions.

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
