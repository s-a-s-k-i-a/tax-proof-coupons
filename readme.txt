=== Tax‑Proof Coupons for WooCommerce ===
Contributors: Jyria
Donate link: https://www.saskialund.de/donate/
Tags: woocommerce, coupon, tax, discount
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.2
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

= 1.0.2 =

Veröffentlichungsdatum: 25. Juli 2025

* Fixed class and method visibility issues.
* Ensured coupon only applies once per order.
* Initial implementation of gross-to-net conversion for fixed-cart coupons.