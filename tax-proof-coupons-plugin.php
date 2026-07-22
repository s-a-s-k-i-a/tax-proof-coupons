<?php
/**
 * Plugin Name:       Tax‑Proof Coupons for WooCommerce
 * Plugin URI:        https://github.com/s-a-s-k-i-a/tax-proof-coupons
 * Description:       Ensure fixed-value coupons always apply after tax, regardless of VAT rate or customer location.
 * Version:           1.0.5
 * Author:            Saskia Teichmann
 * Author URI:        https://saskialund.de
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       taxproof-coupons-for-woocommerce
 * Requires Plugins:  woocommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-coupon-service.php';
require_once __DIR__ . '/includes/integrations/class-storeabill-integration.php';
require_once __DIR__ . '/includes/integrations/class-wpml-integration.php';
require_once __DIR__ . '/includes/class-plugin.php';

add_action( 'plugins_loaded', array( Plugin::class, 'instance' ) );
