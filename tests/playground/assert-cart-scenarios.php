<?php
/**
 * Exercise coupon calculations against real WooCommerce cart internals.
 *
 * Run only in a disposable WordPress Playground instance:
 * wp eval-file tests/playground/assert-cart-scenarios.php
 *
 * @package TaxProofCouponsForWooCommerce
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Cart_Totals' ) ) {
	WP_CLI::error( 'WooCommerce must be active before running cart assertions.' );
}

wc_load_cart();
WC()->customer->set_billing_country( 'DE' );
WC()->customer->set_shipping_country( 'DE' );

/**
 * Fail when two currency values differ by more than one cent.
 *
 * @param string $label    Assertion label.
 * @param float  $actual   Actual value.
 * @param float  $expected Expected value.
 */
function tpc_e2e_assert_currency( string $label, float $actual, float $expected ): void {
	if ( abs( $actual - $expected ) > 0.011 ) {
		WP_CLI::error( sprintf( '%1$s: expected %2$.4f, got %3$.4f.', $label, $expected, $actual ) );
	}
}

/**
 * Calculate one deterministic cart scenario and verify a direct second totals run.
 *
 * @param string             $name           Scenario name.
 * @param array<string, int> $products       SKU to quantity map.
 * @param string             $coupon_code    Coupon code.
 * @param float              $expected_gross Expected gross coupon value.
 * @param float              $expected_total Expected cart total.
 * @param bool               $prices_incl    Whether catalog prices include tax.
 * @return array<string, float|string>
 */
function tpc_e2e_run_cart_scenario( string $name, array $products, string $coupon_code, float $expected_gross, float $expected_total, bool $prices_incl = false ): array {
	update_option( 'woocommerce_prices_include_tax', $prices_incl ? 'yes' : 'no' );
	WC_Cache_Helper::get_transient_version( 'tax', true );
	WC()->cart->empty_cart( true );

	foreach ( $products as $sku => $quantity ) {
		$product_id = wc_get_product_id_by_sku( $sku );

		if ( ! $product_id || ! WC()->cart->add_to_cart( $product_id, $quantity ) ) {
			WP_CLI::error( sprintf( '%1$s: could not add SKU %2$s.', $name, $sku ) );
		}
	}

	if ( ! WC()->cart->apply_coupon( $coupon_code ) ) {
		WP_CLI::error( sprintf( '%1$s: could not apply coupon %2$s.', $name, $coupon_code ) );
	}

	WC()->cart->calculate_totals();

	$first_net   = (float) WC()->cart->get_coupon_discount_amount( $coupon_code, true );
	$first_tax   = (float) WC()->cart->get_coupon_discount_tax_amount( $coupon_code );
	$first_gross = $first_net + $first_tax;
	$first_total = (float) WC()->cart->get_total( 'edit' );

	tpc_e2e_assert_currency( $name . ' gross discount', $first_gross, $expected_gross );
	tpc_e2e_assert_currency( $name . ' cart total', $first_total, $expected_total );

	// Compatibility plugins may instantiate WC_Cart_Totals directly. This bypasses
	// woocommerce_before_calculate_totals and previously reused exhausted state.
	new WC_Cart_Totals( WC()->cart );

	$repeat_net   = (float) WC()->cart->get_coupon_discount_amount( $coupon_code, true );
	$repeat_tax   = (float) WC()->cart->get_coupon_discount_tax_amount( $coupon_code );
	$repeat_gross = $repeat_net + $repeat_tax;
	$repeat_total = (float) WC()->cart->get_total( 'edit' );

	tpc_e2e_assert_currency( $name . ' repeated gross discount', $repeat_gross, $first_gross );
	tpc_e2e_assert_currency( $name . ' repeated cart total', $repeat_total, $first_total );

	return array(
		'name'           => $name,
		'discount_net'   => $repeat_net,
		'discount_tax'   => $repeat_tax,
		'discount_gross' => $repeat_gross,
		'total'          => $repeat_total,
	);
}

$results   = array();
$results[] = tpc_e2e_run_cart_scenario( 'base-ex-tax', array( 'TPC-E2E-33' => 1 ), 'taxproof35', 35.00, 4.27 );
$results[] = tpc_e2e_run_cart_scenario(
	'mixed-rates',
	array(
		'TPC-E2E-100-19' => 1,
		'TPC-E2E-10-7'   => 1,
	),
	'taxproof50',
	50.00,
	79.70
);
$results[] = tpc_e2e_run_cart_scenario(
	'oversized-coupon',
	array(
		'TPC-E2E-8.90'  => 1,
		'TPC-E2E-7.90'  => 1,
		'TPC-E2E-14.90' => 2,
	),
	'taxproof50',
	46.60,
	0.00,
	true
);
$results[] = tpc_e2e_run_cart_scenario( 'prices-including-tax', array( 'TPC-E2E-GROSS-39.27' => 1 ), 'taxproof35', 35.00, 4.27, true );

update_option( 'woocommerce_prices_include_tax', 'no' );
WC()->cart->empty_cart( true );
update_option( 'tpc_e2e_cart_results', $results, false );

WP_CLI::log( wp_json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
WP_CLI::success( 'All Tax-Proof coupon cart scenarios passed.' );
