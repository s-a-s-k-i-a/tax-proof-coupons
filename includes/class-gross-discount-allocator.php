<?php
/**
 * Pure gross-to-net discount allocation.
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allocates one WooCommerce line discount against a gross coupon remainder.
 */
final class Gross_Discount_Allocator {
	/**
	 * Allocate the current line share.
	 *
	 * @param float $proposed_net    Net discount proposed by WooCommerce.
	 * @param float $discounting_net Remaining net value of the current line.
	 * @param float $tax_factor      Gross divided by net for the current line.
	 * @param float $remaining_gross Gross coupon value still to allocate.
	 * @return array<string, float>
	 */
	public static function allocate( float $proposed_net, float $discounting_net, float $tax_factor, float $remaining_gross ): array {
		$proposed_net    = max( 0.0, $proposed_net );
		$discounting_net = max( 0.0, $discounting_net );
		$tax_factor      = max( 1.0, $tax_factor );
		$remaining_gross = max( 0.0, $remaining_gross );

		$candidate_net   = min( $proposed_net, $discounting_net );
		$candidate_gross = $candidate_net * $tax_factor;
		$applied_gross   = min( $candidate_gross, $remaining_gross );
		$applied_net     = min( $discounting_net, $applied_gross / $tax_factor );
		$applied_gross   = $applied_net * $tax_factor;

		return array(
			'net'             => $applied_net,
			'gross'           => $applied_gross,
			'remaining_gross' => max( 0.0, $remaining_gross - $applied_gross ),
		);
	}
}
