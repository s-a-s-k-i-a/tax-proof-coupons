<?php
/**
 * Plugin Name:       Tax‑Proof Coupons for WooCommerce
 * Plugin URI:        https://github.com/s-a-s-k-i-a/tax-proof-coupons
 * Description:       Convert enabled fixed-cart coupon values from gross to net across eligible WooCommerce products.
 * Version:           1.0.9
 * Author:            Saskia Teichmann
 * Author URI:        https://isla-stud.io
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       taxproof-coupons-for-woocommerce
 * Requires Plugins:  woocommerce
 * Requires PHP:      7.4
 * WC requires at least: 8.8
 * WC tested up to:      10.9
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-gross-discount-allocator.php';
require_once __DIR__ . '/includes/class-coupon-service.php';
require_once __DIR__ . '/includes/integrations/class-storeabill-integration.php';
require_once __DIR__ . '/includes/integrations/class-wpml-integration.php';
require_once __DIR__ . '/includes/class-plugin.php';

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action( 'plugins_loaded', array( Plugin::class, 'instance' ) );
