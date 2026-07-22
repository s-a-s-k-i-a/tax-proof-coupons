<?php
/**
 * StoreaBill / Germanized Pro compatibility layer.
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce\Integrations;

use STstudio\TaxProofCouponsForWooCommerce\Coupon_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep StoreaBill discount and voucher totals aligned with the coupon gross amount.
 */
final class StoreaBill_Integration {
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
	 * Determine whether StoreaBill/Germanized Pro is available.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( '\Vendidero\StoreaBill\Document\Document' ) || class_exists( '\Vendidero\Germanized\Pro\StoreaBill\AccountingHelper' );
	}

	/**
	 * Register StoreaBill compatibility hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'storeabill_invoice_get_discount_total', array( $this, 'filter_invoice_discount_total' ), 10, 2 );
		add_filter( 'storeabill_woo_order_voucher_total', array( $this, 'filter_voucher_total' ), 10, 2 );
		add_filter( 'storeabill_woo_order_voucher_tax', array( $this, 'filter_voucher_tax' ), 10, 2 );
	}

	/**
	 * Override StoreaBill's invoice discount total with the gross coupon amount.
	 *
	 * @param float $total   Current discount total.
	 * @param mixed $invoice Invoice document.
	 * @return float
	 */
	public function filter_invoice_discount_total( float $total, $invoice ): float {
		$order = $this->resolve_order( $invoice );

		if ( ! $order || ! $this->coupon_service->order_has_tax_proof_coupons( $order ) ) {
			return $total;
		}

		return $this->coupon_service->get_order_discount_total( $order );
	}

	/**
	 * Override the StoreaBill voucher total with the gross coupon amount.
	 *
	 * @param float $total Current voucher total.
	 * @param mixed $order StoreaBill order wrapper or Woo order.
	 * @return float
	 */
	public function filter_voucher_total( float $total, $order ): float {
		$resolved_order = $this->resolve_order( $order );

		if ( ! $resolved_order || ! $this->coupon_service->order_has_tax_proof_coupons( $resolved_order ) ) {
			return $total;
		}

		return $this->coupon_service->get_order_discount_total( $resolved_order );
	}

	/**
	 * Override the StoreaBill voucher tax amount with the persisted tax component.
	 *
	 * @param float $total Current voucher tax total.
	 * @param mixed $order StoreaBill order wrapper or Woo order.
	 * @return float
	 */
	public function filter_voucher_tax( float $total, $order ): float {
		$resolved_order = $this->resolve_order( $order );

		if ( ! $resolved_order || ! $this->coupon_service->order_has_tax_proof_coupons( $resolved_order ) ) {
			return $total;
		}

		return $this->coupon_service->get_order_coupon_tax_total( $resolved_order );
	}

	/**
	 * Resolve a WooCommerce order from a StoreaBill wrapper or direct order instance.
	 *
	 * @param mixed $subject StoreaBill object or WC_Order.
	 * @return \WC_Order|null
	 */
	private function resolve_order( $subject ): ?\WC_Order {
		for ( $depth = 0; $depth < 4; $depth++ ) {
			if ( $subject instanceof \WC_Order ) {
				return $subject;
			}

			if ( ! is_object( $subject ) || ! is_callable( array( $subject, 'get_order' ) ) ) {
				return null;
			}

			$order = $subject->get_order();

			if ( $order === $subject ) {
				return null;
			}

			$subject = $order;
		}

		return null;
	}
}
