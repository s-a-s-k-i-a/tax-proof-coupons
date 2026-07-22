<?php
/**
 * Plugin bootstrap.
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce;

use STstudio\TaxProofCouponsForWooCommerce\Integrations\StoreaBill_Integration;
use STstudio\TaxProofCouponsForWooCommerce\Integrations\WPML_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap the plugin services and integrations.
 */
final class Plugin {
	/**
	 * Plugin version.
	 */
	public const VERSION = '1.0.5';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Core coupon service.
	 *
	 * @var Coupon_Service
	 */
	private Coupon_Service $coupon_service;

	/**
	 * Create the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->coupon_service = new Coupon_Service();
		$this->coupon_service->register_hooks();

		if ( WPML_Integration::is_active() ) {
			( new WPML_Integration( $this->coupon_service ) )->register_hooks();
		}

		if ( StoreaBill_Integration::is_active() ) {
			( new StoreaBill_Integration( $this->coupon_service ) )->register_hooks();
		}
	}
}
