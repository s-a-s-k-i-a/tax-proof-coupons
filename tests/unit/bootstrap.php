<?php
/**
 * PHPUnit bootstrap.
 *
 * @package TaxProofCouponsForWooCommerce
 */

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Return scalar test input unchanged.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	function wp_unslash( string $value ): string {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitize scalar form input for the unit contract.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	function sanitize_text_field( string $value ): string {
		return trim( $value );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Accept only the explicit unit-test coupon nonce.
	 *
	 * @param string $nonce  Submitted nonce.
	 * @param string $action Expected action.
	 * @return bool
	 */
	function wp_verify_nonce( string $nonce, string $action ): bool {
		return 'valid-coupon-nonce' === $nonce && 'woocommerce_save_data' === $action;
	}
}

require_once __DIR__ . '/stubs/class-wc-coupon.php';
require_once __DIR__ . '/stubs/class-wc-order-item-coupon.php';
require_once __DIR__ . '/stubs/class-wc-order.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-gross-discount-allocator.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-coupon-service.php';
require_once dirname( __DIR__, 2 ) . '/includes/integrations/class-storeabill-integration.php';
