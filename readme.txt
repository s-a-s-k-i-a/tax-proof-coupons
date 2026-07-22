=== Tax‑Proof Coupons for WooCommerce ===
Contributors: Jyria
Donate link: https://isla-stud.io/donate/
Tags: woocommerce, coupon, tax, discount
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ensure fixed-value coupons always apply after tax, regardless of customer location or VAT rate.

== Description ==
Tax‑Proof Coupons for WooCommerce adds a simple checkbox “Apply coupon after tax” to the coupon edit screen. When enabled on a fixed-cart coupon, the plugin converts each gross coupon share using the tax rate of the discounted line. The intended gross value stays stable across eligible cart items and customer tax locations, subject to WooCommerce currency precision and the discountable cart value.

== Installation ==
1. Upload the `taxproof-coupons-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen in WordPress.
3. In WooCommerce → Coupons, edit a fixed-cart coupon and check **Apply coupon after tax**.

== Frequently Asked Questions ==
= Why is this needed? =
By default, WooCommerce adjusts fixed-cart coupons by the current VAT rate, causing the discount to vary by customer location. Tax‑Proof Coupons ensures a fixed gross coupon value remains fixed across all taxes.

== Changelog ==

= 1.0.6 =

Release date: July 22, 2026

* Fix negative completed-order totals in the WPML/WCML compatibility path.
* Allocate gross discounts per line tax rate for mixed-rate carts.
* Keep repeated WooCommerce totals calculations deterministic for compatibility plugins.
* Use the supported checkout coupon-item hook and HPOS-safe persistence.
* Preserve native WooCommerce behavior when catalog prices include tax.
* Add automated unit, cart, Checkout Block, HPOS, and WPML-contract regression tests.

= 1.0.5 =

Release date: March 2026

* Refactored the plugin into a cleaner core service plus isolated WPML and StoreaBill compatibility layers.
* Fixed fixed-cart coupons whose configured gross amount is larger than the discountable cart total.
* Removed release-time debug logging and broad total-manipulation hooks that caused rounding drift in edge cases.
* Persist the gross, net, and tax components on order coupon items so Germanized Pro / StoreaBill can invoice consistently.
* Keep the displayed coupon amount capped to the effective gross discount that can actually be applied.

= 1.0.4 =

Release date: January 2025

* **Verbesserte Präzision**: Neue präzise Berechnungsmethode für exakte Steuerumrechnungen
* **Erweiterte Anzeige-Funktionen**: Verbesserte Coupon-Anzeige im Warenkorb und Checkout
* **StoreaBill/Germanized Pro Integration**: Vollständige Kompatibilität mit Germanized Pro für Rechnungsgenerierung
* **Admin-Verbesserungen**: Detaillierte Anzeige von Netto- und Bruttobeträgen in der WooCommerce Admin-Oberfläche
* **Erweiterte Metadaten-Speicherung**: Präzise Speicherung von Coupon-Beträgen mit hoher Genauigkeit
* **Debug-Funktionen**: Erweiterte Logging-Funktionen für bessere Entwicklung und Fehlerbehebung
* **Hook-Integration**: Neue Hooks für bessere Integration mit WooCommerce und Drittanbieter-Plugins
* **Performance-Optimierungen**: Verbesserte Berechnungslogik für komplexe Steuerszenarien

= 1.0.3 =

Release date: August 3, 2025

* Ensuring unique namespace
* Added Requires plugins plugin header

= 1.0.2 =

Release date: July 25th 2025

* Fixed class and method visibility issues.
* Ensured coupon only applies once per order.
* Initial implementation of gross-to-net conversion for fixed-cart coupons.
