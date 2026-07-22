<?php
/**
 * Build a deterministic WooCommerce storefront for browser regression tests.
 *
 * Run only in a disposable WordPress Playground instance:
 * wp eval-file tests/playground/setup-store.php
 *
 * @package TaxProofCouponsForWooCommerce
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
	WP_CLI::error( 'WooCommerce must be active before preparing the test store.' );
}

update_option( 'blogname', 'Tax-Proof Coupons E2E' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'woocommerce_default_country', 'DE' );
update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_calc_taxes', 'yes' );
update_option( 'woocommerce_prices_include_tax', 'no' );
update_option( 'woocommerce_tax_display_shop', 'incl' );
update_option( 'woocommerce_tax_display_cart', 'incl' );
update_option( 'woocommerce_tax_total_display', 'itemized' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_enable_checkout_login_reminder', 'no' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'            => 'yes',
		'title'              => 'Cash on delivery',
		'description'        => 'Browser regression test payment method.',
		'instructions'       => 'No payment is collected in this disposable test store.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	)
);

global $wpdb;

$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$tax_rate_id = WC_Tax::_insert_tax_rate( // phpcs:ignore PHPCompatibility.MethodUse.NewStaticMethods
	array(
		'tax_rate_country'  => 'DE',
		'tax_rate_state'    => '',
		'tax_rate'          => '19.0000',
		'tax_rate_name'     => 'VAT 19%',
		'tax_rate_priority' => 1,
		'tax_rate_compound' => 0,
		'tax_rate_shipping' => 1,
		'tax_rate_order'    => 1,
		'tax_rate_class'    => '',
	)
);

$reduced_tax_rate_id = WC_Tax::_insert_tax_rate( // phpcs:ignore PHPCompatibility.MethodUse.NewStaticMethods
	array(
		'tax_rate_country'  => 'DE',
		'tax_rate_state'    => '',
		'tax_rate'          => '7.0000',
		'tax_rate_name'     => 'VAT 7%',
		'tax_rate_priority' => 1,
		'tax_rate_compound' => 0,
		'tax_rate_shipping' => 0,
		'tax_rate_order'    => 2,
		'tax_rate_class'    => 'reduced-rate',
	)
);

/**
 * Create or update a test product.
 *
 * @param string $sku       Product SKU.
 * @param string $name      Product name.
 * @param string $slug      Product slug.
 * @param string $price     Catalog price.
 * @param string $tax_class WooCommerce tax class slug.
 * @return int
 */
function tpc_e2e_save_product( string $sku, string $name, string $slug, string $price, string $tax_class = '' ): int {
	$existing_id = wc_get_product_id_by_sku( $sku );
	$product     = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();

	$product->set_name( $name );
	$product->set_slug( $slug );
	$product->set_sku( $sku );
	$product->set_regular_price( $price );
	$product->set_price( $price );
	$product->set_tax_status( 'taxable' );
	$product->set_tax_class( $tax_class );
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );
	$product->set_virtual( true );

	return $product->save();
}

/**
 * Create or update a Tax-Proof test coupon.
 *
 * @param string $code   Coupon code.
 * @param string $amount Gross coupon amount.
 * @return int
 */
function tpc_e2e_save_coupon( string $code, string $amount ): int {
	$coupon = new WC_Coupon( $code );
	$coupon->set_code( $code );
	$coupon->set_discount_type( 'fixed_cart' );
	$coupon->set_amount( $amount );
	$coupon->set_individual_use( false );
	$coupon->set_status( 'publish' );
	$coupon->update_meta_data( 'tpc_apply_after_tax', 'yes' );
	$coupon->save();

	return $coupon->get_id();
}

$product_id = tpc_e2e_save_product( 'TPC-E2E-33', 'Tax test product', 'tax-test-product', '33.00' );
tpc_e2e_save_product( 'TPC-E2E-100-19', 'Mixed VAT 19 product', 'mixed-vat-19-product', '100.00' );
tpc_e2e_save_product( 'TPC-E2E-10-7', 'Mixed VAT 7 product', 'mixed-vat-7-product', '10.00', 'reduced-rate' );
tpc_e2e_save_product( 'TPC-E2E-8.90', 'Oversized coupon product A', 'oversized-coupon-product-a', '8.90' );
tpc_e2e_save_product( 'TPC-E2E-7.90', 'Oversized coupon product B', 'oversized-coupon-product-b', '7.90' );
tpc_e2e_save_product( 'TPC-E2E-14.90', 'Oversized coupon product C', 'oversized-coupon-product-c', '14.90' );
tpc_e2e_save_product( 'TPC-E2E-GROSS-39.27', 'Tax-inclusive product', 'tax-inclusive-product', '39.27' );

$coupon_id = tpc_e2e_save_coupon( 'taxproof35', '35.00' );
tpc_e2e_save_coupon( 'taxproof50', '50.00' );

WC_Cache_Helper::get_transient_version( 'tax', true );
wc_delete_product_transients();
flush_rewrite_rules();

WP_CLI::success(
	sprintf(
		'Test store prepared (product %1$d, coupon %2$d, tax rates %3$d/%4$d).',
		$product_id,
		$coupon_id,
		$tax_rate_id,
		$reduced_tax_rate_id
	)
);
