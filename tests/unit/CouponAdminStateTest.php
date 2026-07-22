<?php
/**
 * Coupon editor state persistence tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

use PHPUnit\Framework\TestCase;
use STstudio\TaxProofCouponsForWooCommerce\Coupon_Service;

/**
 * Verify unsupported coupon types cannot retain the after-tax flag.
 */
final class CouponAdminStateTest extends TestCase {
	/**
	 * Reset the simulated coupon form after each test.
	 */
	protected function tearDown(): void {
		$_POST = array();
	}

	/**
	 * A checked fixed-cart coupon must persist the opt-in.
	 */
	public function test_saves_enabled_state_for_fixed_cart_coupon(): void {
		$coupon = new WC_Coupon( 'fixed_cart' );

		$this->submit_coupon( $coupon, 'yes' );

		self::assertSame( 'yes', $coupon->get_meta( 'tpc_apply_after_tax', true ) );
	}

	/**
	 * An unchecked fixed-cart coupon must remove a previous opt-in.
	 */
	public function test_removes_enabled_state_from_unchecked_fixed_cart_coupon(): void {
		$coupon = new WC_Coupon( 'fixed_cart', array( 'tpc_apply_after_tax' => 'yes' ) );

		$this->submit_coupon( $coupon );

		self::assertSame( '', $coupon->get_meta( 'tpc_apply_after_tax', true ) );
	}

	/**
	 * A malformed checkbox value must be handled as disabled.
	 */
	public function test_rejects_non_scalar_checkbox_value(): void {
		$coupon = new WC_Coupon( 'fixed_cart', array( 'tpc_apply_after_tax' => 'yes' ) );

		$_POST                        = array( 'woocommerce_meta_nonce' => 'valid-coupon-nonce' );
		$_POST['tpc_apply_after_tax'] = array( 'yes' );

		( new Coupon_Service() )->save_apply_after_tax_checkbox( 123, $coupon );

		self::assertSame( '', $coupon->get_meta( 'tpc_apply_after_tax', true ) );
	}

	/**
	 * Unsupported types must reject even a forged checked value.
	 *
	 * @dataProvider unsupported_coupon_types
	 * @param string $discount_type Coupon discount type.
	 */
	public function test_removes_enabled_state_from_unsupported_coupon_type( string $discount_type ): void {
		$coupon = new WC_Coupon( $discount_type, array( 'tpc_apply_after_tax' => 'yes' ) );

		$this->submit_coupon( $coupon, 'yes' );

		self::assertSame( '', $coupon->get_meta( 'tpc_apply_after_tax', true ) );
	}

	/**
	 * Return native and unknown unsupported coupon types.
	 *
	 * @return array<string, array{string}>
	 */
	public function unsupported_coupon_types(): array {
		return array(
			'percentage'    => array( 'percent' ),
			'fixed product' => array( 'fixed_product' ),
			'custom type'   => array( 'third_party_type' ),
		);
	}

	/**
	 * Submit one simulated WooCommerce coupon form.
	 *
	 * @param WC_Coupon   $coupon Coupon being saved.
	 * @param string|null $value  Optional checkbox value.
	 */
	private function submit_coupon( WC_Coupon $coupon, ?string $value = null ): void {
		$_POST = array( 'woocommerce_meta_nonce' => 'valid-coupon-nonce' );

		if ( null !== $value ) {
			$_POST['tpc_apply_after_tax'] = $value;
		}

		( new Coupon_Service() )->save_apply_after_tax_checkbox( 123, $coupon );
	}
}
