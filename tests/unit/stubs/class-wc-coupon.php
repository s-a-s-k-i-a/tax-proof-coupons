<?php
/**
 * Minimal WooCommerce coupon contract for unit tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

/**
 * Store coupon type and metadata for admin persistence tests.
 */
class WC_Coupon {
	/**
	 * Coupon discount type.
	 *
	 * @var string
	 */
	private string $discount_type;

	/**
	 * Coupon metadata.
	 *
	 * @var array<string, string>
	 */
	private array $meta;

	/**
	 * Create the coupon stub.
	 *
	 * @param string                $discount_type Coupon discount type.
	 * @param array<string, string> $meta          Initial coupon metadata.
	 */
	public function __construct( string $discount_type, array $meta = array() ) {
		$this->discount_type = $discount_type;
		$this->meta          = $meta;
	}

	/**
	 * Return the coupon discount type.
	 *
	 * @param string $context Data access context.
	 * @return string
	 */
	public function get_discount_type( string $context = 'view' ): string {
		unset( $context );

		return $this->discount_type;
	}

	/**
	 * Store one metadata value.
	 *
	 * @param string $key   Metadata key.
	 * @param string $value Metadata value.
	 */
	public function update_meta_data( string $key, string $value ): void {
		$this->meta[ $key ] = $value;
	}

	/**
	 * Remove one metadata value.
	 *
	 * @param string $key Metadata key.
	 */
	public function delete_meta_data( string $key ): void {
		unset( $this->meta[ $key ] );
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
	 * Match the WooCommerce persistence contract.
	 */
	public function save(): void {
	}
}
