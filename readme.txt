=== Tax‑Proof Coupons for WooCommerce ===
Contributors: Jyria
Donate link: https://www.saskialund.de/donate/
Tags: woocommerce, coupon, tax, discount
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ensure fixed-value coupons always apply after tax, regardless of customer location or VAT rate.

== Description ==
Tax‑Proof Coupons for WooCommerce adds a simple checkbox “Apply coupon after tax” to the coupon edit screen. When enabled on a fixed-cart coupon, the plugin converts the gross coupon value you enter into the correct net discount and applies it across the cart items—guaranteeing the exact gross amount is deducted, no matter the VAT rate or customer location.

== Installation ==
1. Upload the `tax-proof-coupons` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen in WordPress.
3. In WooCommerce → Coupons, edit a fixed-cart coupon and check **Apply coupon after tax**.

== Frequently Asked Questions ==
= Why is this needed? =
By default, WooCommerce adjusts fixed-cart coupons by the current VAT rate, causing the discount to vary by customer location. Tax‑Proof Coupons ensures a fixed gross coupon value remains fixed across all taxes.

== Screenshots ==
1. Coupon edit screen showing the new checkbox.

== Changelog ==

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

Release date: August 3rd 2025

* Ensuring unique namespace
* Added Requires plugins plugin header

= 1.0.2 =

Release date: July 25th 2025

* Fixed class and method visibility issues.
* Ensured coupon only applies once per order.
* Initial implementation of gross-to-net conversion for fixed-cart coupons.
