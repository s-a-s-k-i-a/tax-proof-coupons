<?php
/**
 * StoreaBill compatibility contract tests.
 *
 * @package TaxProofCouponsForWooCommerce
 */

use PHPUnit\Framework\TestCase;
use STstudio\TaxProofCouponsForWooCommerce\Coupon_Service;
use STstudio\TaxProofCouponsForWooCommerce\Integrations\StoreaBill_Integration;

/**
 * Verify StoreaBill objects resolve to the underlying WooCommerce order.
 */
final class StoreaBillIntegrationTest extends TestCase {
	/**
	 * StoreaBill invoice and order wrappers must resolve to WC_Order.
	 */
	public function test_resolves_nested_storeabill_order_wrapper_for_invoice_discount(): void {
		$coupon_item = new WC_Order_Item_Coupon(
			array(
				'_tpc_applied_after_tax' => 'yes',
				'_tpc_gross_discount'    => '5.000000',
				'_tpc_net_discount'      => '4.390000',
				'_tpc_tax_component'     => '0.610000',
			)
		);
		$order       = new WC_Order( array( $coupon_item ) );
		$order_layer = $this->getMockBuilder( stdClass::class )->addMethods( array( 'get_order' ) )->getMock();
		$order_layer->method( 'get_order' )->willReturn( $order );
		$invoice = $this->getMockBuilder( stdClass::class )->addMethods( array( 'get_order' ) )->getMock();
		$invoice->method( 'get_order' )->willReturn( $order_layer );
		$integration = new StoreaBill_Integration( new Coupon_Service() );

		self::assertSame( 5.0, $integration->filter_invoice_discount_total( 4.99, $invoice ) );
	}

	/**
	 * Unsupported subjects must preserve StoreaBill's incoming value.
	 */
	public function test_keeps_storeabill_value_for_an_unsupported_subject(): void {
		$integration = new StoreaBill_Integration( new Coupon_Service() );

		self::assertSame( 4.99, $integration->filter_invoice_discount_total( 4.99, new stdClass() ) );
	}
}
