<?php
/**
 * Minimal WooCommerce order contract for unit tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

/**
 * Expose coupon items to the StoreaBill contract test.
 */
class WC_Order {
	/**
	 * Coupon items.
	 *
	 * @var array<int, WC_Order_Item_Coupon>
	 */
	private array $coupon_items;

	/**
	 * Create an order stub.
	 *
	 * @param array<int, WC_Order_Item_Coupon> $coupon_items Coupon items.
	 */
	public function __construct( array $coupon_items ) {
		$this->coupon_items = $coupon_items;
	}

	/**
	 * Return items of the requested type.
	 *
	 * @param string $type Item type.
	 * @return array<int, WC_Order_Item_Coupon>
	 */
	public function get_items( string $type ): array {
		return 'coupon' === $type ? $this->coupon_items : array();
	}
}
