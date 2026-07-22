=== Tax‑Proof Coupons for WooCommerce ===
Contributors: Jyria
Donate link: https://isla-stud.io/donate/
Tags: woocommerce, coupon, tax, discount
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Apply selected fixed-cart coupons as gross discounts across eligible WooCommerce products, including mixed tax rates.

== Description ==
Tax‑Proof Coupons for WooCommerce lets you define an eligible fixed-cart coupon as a gross amount: the amount your customer should actually see deducted after tax.

= What does it do? =

The plugin adds an **Apply coupon after tax** checkbox to fixed-cart coupons. When the option is enabled, each part of the coupon is converted using the tax rate of the product line it discounts.

For example, a 35.00 EUR coupon reduces eligible products by 35.00 EUR including tax, within the configured currency precision. WooCommerce still stores the required net discount and tax portion internally.

This is useful when a fixed promotional value should not become larger or smaller merely because eligible products use different VAT rates or the customer's tax location changes.

= What are the limits? =

* Only fixed-cart coupons with **Apply coupon after tax** enabled are changed. Other coupons keep WooCommerce's native behavior.
* The effective discount cannot exceed the value of the eligible product lines. Shipping, fees, excluded products, and other non-discountable amounts do not increase that limit.
* Displayed and stored amounts follow WooCommerce's configured currency precision and rounding.
* The plugin does not choose tax rates, change product prices, replace a tax engine, or provide tax or legal advice.

= Compatibility =

Version 1.0.8 is tested with WordPress 7.0, WooCommerce 10.9.4, Checkout Block, High-Performance Order Storage (HPOS), Germanized 4.0.10, and Germanized Pro/StoreaBill 4.3.4. Dedicated compatibility paths exist for WPML/WooCommerce Multilingual and Germanized Pro/StoreaBill. Compatibility still depends on the versions and configuration used by the store; report reproducible conflicts through the GitHub issue tracker or WordPress.org support forum.

== Installation ==
1. Upload the `taxproof-coupons-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** screen in WordPress.
3. In WooCommerce → Coupons, edit a fixed-cart coupon and check **Apply coupon after tax**.

== Frequently Asked Questions ==
= Why is this needed? =
WooCommerce calculates fixed-cart discounts as net values before tax. If the amount entered by a shop owner is intended as a gross promotional value, its visible effect can otherwise vary with the applicable tax rate. This plugin performs the gross-to-net conversion for enabled coupons while leaving WooCommerce responsible for tax calculation.

= Do I need to recreate existing coupons after updating? =
No. Existing coupon settings remain unchanged. Version 1.0.8 corrects how StoreaBill resolves the persisted gross invoice discount; the WooCommerce calculation fixes were introduced in 1.0.6.

= What happens if the coupon is larger than the eligible products? =
The discount is capped at the discountable gross value of the eligible product lines. It does not create a negative payable amount or consume shipping and fees merely to reach the configured coupon value.

= Does it work with products that have different tax rates? =
Yes. The plugin allocates the coupon across eligible product lines and converts each share using that line's tax rate. The final displayed value is subject to WooCommerce currency precision and rounding.

= Does the plugin change my tax rates or guarantee tax compliance? =
No. WooCommerce and the store's tax configuration determine the rates. The plugin only changes how an enabled fixed-cart coupon is converted into WooCommerce's net discount values. Store owners remain responsible for validating their tax and invoice setup.

== Changelog ==

= 1.0.8 =

Release date: July 22, 2026

* **For store owners:** Fixed a possible one-cent difference in StoreaBill's aggregate invoice discount for small gross coupons in mixed-tax orders. Invoice totals, taxes, and coupon settings remain unchanged.
* **For developers:** Resolve StoreaBill's nested invoice/order wrappers before reading persisted WooCommerce coupon metadata.
* Added a StoreaBill wrapper-contract regression and verified synchronized, finalized PDFs with Germanized Pro/StoreaBill 4.3.4.

= 1.0.7 =

Release date: July 22, 2026

* **For store owners:** Added a plain-language explanation, a gross-discount example, clearer limits, and upgrade guidance. No coupon calculation or setting changed in this release.
* **For developers:** Documented the exact WooCommerce calculation boundary and qualified compatibility claims.
* Corrected the installation folder name and historical release dates.

= 1.0.6 =

Release date: July 22, 2026

* **For store owners:** Fixed negative saved order totals in the WPML/WCML path, mixed-tax carts, oversized coupons, and unstable results during repeated cart calculations.
* **For developers:** Added per-line gross allocation, reset state for every WooCommerce coupon distribution, corrected the checkout coupon-item hook, and made persistence HPOS-safe.
* Preserved native WooCommerce behavior when catalog prices include tax.
* Added automated unit, cart, Checkout Block, HPOS, Advanced Dynamic Pricing, and WPML-contract regression tests.

= 1.0.5 =

Release date: March 7, 2026

* Refactored the plugin into a cleaner core service plus isolated WPML and StoreaBill compatibility layers.
* Fixed fixed-cart coupons whose configured gross amount is larger than the discountable cart total.
* Removed release-time debug logging and broad total-manipulation hooks that caused rounding drift in edge cases.
* Persist the gross, net, and tax components on order coupon items so Germanized Pro / StoreaBill can invoice consistently.
* Keep the displayed coupon amount capped to the effective gross discount that can actually be applied.

= 1.0.4 =

Release date: October 20, 2025

* **Verbesserte Präzision**: Neue präzise Berechnungsmethode für exakte Steuerumrechnungen
* **Erweiterte Anzeige-Funktionen**: Verbesserte Coupon-Anzeige im Warenkorb und Checkout
* **StoreaBill/Germanized Pro Integration**: Vollständige Kompatibilität mit Germanized Pro für Rechnungsgenerierung
* **Admin-Verbesserungen**: Detaillierte Anzeige von Netto- und Bruttobeträgen in der WooCommerce Admin-Oberfläche
* **Erweiterte Metadaten-Speicherung**: Präzise Speicherung von Coupon-Beträgen mit hoher Genauigkeit
* **Debug-Funktionen**: Erweiterte Logging-Funktionen für bessere Entwicklung und Fehlerbehebung
* **Hook-Integration**: Neue Hooks für bessere Integration mit WooCommerce und Drittanbieter-Plugins
* **Performance-Optimierungen**: Verbesserte Berechnungslogik für komplexe Steuerszenarien

= 1.0.3 =

Release date: August 11, 2025

* Ensuring unique namespace
* Added Requires plugins plugin header

= 1.0.2 =

Release date: July 25th 2025

* Fixed class and method visibility issues.
* Ensured coupon only applies once per order.
* Initial implementation of gross-to-net conversion for fixed-cart coupons.
