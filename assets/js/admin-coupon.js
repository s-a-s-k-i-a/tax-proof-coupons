( function( $ ) {
	'use strict';

	$( function() {
		var $couponType = $( '#discount_type' );
		var $checkbox = $( '#tpc_apply_after_tax' );
		var $description = $( '#tpc_apply_after_tax_description' );

		if ( ! $couponType.length || ! $checkbox.length || ! $description.length ) {
			return;
		}

		function synchronizeCouponType() {
			var isSupported = 'fixed_cart' === $couponType.val();
			var description = isSupported
				? $checkbox.attr( 'data-supported-description' )
				: $checkbox.attr( 'data-unsupported-description' );

			if ( ! isSupported ) {
				$checkbox.prop( 'checked', false );
			}

			$checkbox
				.prop( 'disabled', ! isSupported )
				.attr( 'aria-disabled', isSupported ? 'false' : 'true' );
			$description.text( description );
		}

		$couponType.on( 'change.taxProofCoupons', synchronizeCouponType );
		synchronizeCouponType();
	} );
}( jQuery ) );
