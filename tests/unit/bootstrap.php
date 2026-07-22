<?php
/**
 * PHPUnit bootstrap.
 *
 * @package TaxProofCouponsForWooCommerce
 */

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

require_once __DIR__ . '/stubs/class-wc-order-item-coupon.php';
require_once __DIR__ . '/stubs/class-wc-order.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-gross-discount-allocator.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-coupon-service.php';
require_once dirname( __DIR__, 2 ) . '/includes/integrations/class-storeabill-integration.php';
