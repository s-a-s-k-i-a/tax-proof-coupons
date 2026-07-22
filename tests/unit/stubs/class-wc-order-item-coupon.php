<?php
/**
 * Minimal WooCommerce coupon item contract for unit tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

/**
 * Represent the coupon data used by the StoreaBill contract test.
 */
class WC_Order_Item_Coupon {
	/**
	 * Coupon metadata.
	 *
	 * @var array<string, string>
	 */
	private array $meta;

	/**
	 * Create a coupon item stub.
	 *
	 * @param array<string, string> $meta Coupon metadata.
	 */
	public function __construct( array $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Return one metadata value.
	 *
	 * @param string $key    Metadata key.
	 * @param bool   $single Whether to return one value.
	 * @return string
	 */
	public function get_meta( string $key, bool $single = true ): string {
		unset( $single );

		return $this->meta[ $key ] ?? '';
	}

	/**
	 * Return the rounded net discount.
	 *
	 * @return float
	 */
	public function get_discount(): float {
		return 4.39;
	}

	/**
	 * Return the rounded discount tax.
	 *
	 * @return float
	 */
	public function get_discount_tax(): float {
		return 0.61;
	}
}
