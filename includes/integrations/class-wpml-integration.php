<?php
/**
 * WPML / WCML compatibility layer.
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce\Integrations;

use STstudio\TaxProofCouponsForWooCommerce\Coupon_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps processed order totals stable when WCML mutates order values post-checkout.
 */
final class WPML_Integration {
	/**
	 * Core coupon service.
	 *
	 * @var Coupon_Service
	 */
	private Coupon_Service $coupon_service;

	/**
	 * Constructor.
	 *
	 * @param Coupon_Service $coupon_service Coupon service.
	 */
	public function __construct( Coupon_Service $coupon_service ) {
		$this->coupon_service = $coupon_service;
	}

	/**
	 * Determine whether WCML/WPML is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WCML_VERSION' ) || class_exists( 'woocommerce_wpml' ) || function_exists( 'wcml_is_multi_currency_on' );
	}

	/**
	 * Register WPML-specific compatibility hooks.
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'fix_order_totals_after_wpml' ), PHP_INT_MAX, 2 );
		add_action( 'woocommerce_payment_complete', array( $this, 'fix_order_totals_after_wpml' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Reapply coupon item totals after WPML/WCML touches the order.
	 *
	 * @param int|\WC_Order $order_id Order object or order ID.
	 * @param array|null    $data     Checkout payload.
	 */
	public function fix_order_totals_after_wpml( $order_id, ?array $data = null ): void {
		unset( $data );

		$order = $order_id instanceof \WC_Order ? $order_id : wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order || ! $this->coupon_service->order_has_tax_proof_coupons( $order ) ) {
			return;
		}

		$updated        = $this->coupon_service->synchronize_order_coupon_items( $order );
		$expected_total = $this->coupon_service->calculate_expected_order_total( $order );
		$current_total  = (float) $order->get_total( 'edit' );

		if ( abs( $current_total - $expected_total ) > 0.01 ) {
			$order->set_total( $expected_total );
			$updated = true;
		}

		if ( $updated ) {
			$order->save();
		}
	}
}
