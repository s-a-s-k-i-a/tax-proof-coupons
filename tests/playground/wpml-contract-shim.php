<?php
/**
 * Plugin Name: WPML/WCML Contract Shim for Tax-Proof Coupons Tests
 * Description: Activates the Tax-Proof Coupons WCML compatibility layer without bundling proprietary WPML code.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 *
 * @package TaxProofCouponsForWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WCML_VERSION' ) ) {
	define( 'WCML_VERSION', 'contract-test' );
}
