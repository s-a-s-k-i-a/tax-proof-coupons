<?php
/**
 * Gross discount allocator tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

use PHPUnit\Framework\TestCase;
use STstudio\TaxProofCouponsForWooCommerce\Gross_Discount_Allocator;

/**
 * Verify line-aware gross-to-net allocation.
 */
final class GrossDiscountAllocatorTest extends TestCase {
	/**
	 * A 35 EUR gross discount at 19 percent becomes the matching net value.
	 */
	public function test_allocates_gross_remainder_at_line_tax_rate(): void {
		$result = Gross_Discount_Allocator::allocate( 33.0, 33.0, 1.19, 35.0 );

		self::assertEqualsWithDelta( 29.4117647, $result['net'], 0.000001 );
		self::assertEqualsWithDelta( 35.0, $result['gross'], 0.000001 );
		self::assertEqualsWithDelta( 0.0, $result['remaining_gross'], 0.000001 );
	}

	/**
	 * The line value remains a hard upper bound.
	 */
	public function test_caps_allocation_to_discountable_line_value(): void {
		$result = Gross_Discount_Allocator::allocate( 50.0, 10.0, 1.07, 50.0 );

		self::assertEqualsWithDelta( 10.0, $result['net'], 0.000001 );
		self::assertEqualsWithDelta( 10.7, $result['gross'], 0.000001 );
		self::assertEqualsWithDelta( 39.3, $result['remaining_gross'], 0.000001 );
	}

	/**
	 * A second tax class consumes only the remaining gross value.
	 */
	public function test_reconciles_mixed_tax_rate_remainder(): void {
		$reduced  = Gross_Discount_Allocator::allocate( 25.0, 10.0, 1.07, 50.0 );
		$standard = Gross_Discount_Allocator::allocate( 100.0, 100.0, 1.19, $reduced['remaining_gross'] );

		self::assertEqualsWithDelta( 10.7, $reduced['gross'], 0.000001 );
		self::assertEqualsWithDelta( 39.3, $standard['gross'], 0.000001 );
		self::assertEqualsWithDelta( 0.0, $standard['remaining_gross'], 0.000001 );
	}
}
