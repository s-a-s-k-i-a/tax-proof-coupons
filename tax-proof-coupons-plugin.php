<?php
/**
 * Plugin Name:       Tax‑Proof Coupons for WooCommerce
 * Plugin URI:        https://github.com/s-a-s-k-i-a/tax-proof-coupons
 * Description:       Ensure fixed-value coupons always apply after tax, regardless of VAT rate or customer location.
 * Version:           1.0.2
 * Author:            Saskia Teichmann
 * Author URI:        https://saskialund.de
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       taxproof-coupons-for-woocommerce
 */

namespace WC\TaxProofCoupons;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main plugin class for Tax‑Proof Coupons for WooCommerce functionality.
 */
class Plugin {
    /** Plugin version. */
    public const VERSION = '1.0.2';

    /** Singleton instance. */
    private static $instance = null;

    /**
     * Get or create the singleton instance.
     *
     * @return Plugin
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    /** Prevent direct instantiation. */
    private function __construct() {}

    /** Initialize WP and WooCommerce hooks. */
    private function init_hooks(): void {
        add_action( 'woocommerce_coupon_options', [ $this, 'add_apply_after_tax_checkbox' ] );
        add_action( 'woocommerce_coupon_options_save', [ $this, 'save_apply_after_tax_checkbox' ], 10, 2 );
        add_filter( 'woocommerce_coupon_get_discount_amount', [ $this, 'apply_coupon_after_tax' ], 20, 5 );
    }

    /** Add "Apply after tax" checkbox to the coupon admin screen. */
    public function add_apply_after_tax_checkbox(): void {
        \woocommerce_wp_checkbox( [
            'id'          => 'tpc_apply_after_tax',
            'label'       => __( 'Apply coupon after tax', 'taxproof-coupons-for-woocommerce' ),
            'description' => __( 'Deduct the fixed coupon amount from the order total including tax, ensuring the coupon value remains constant across all tax rates and locations.', 'taxproof-coupons-for-woocommerce' ),
        ] );
    }

    /**
     * Save the "Apply after tax" checkbox value.
     *
     * @param int       $post_id Coupon post ID.
     * @param \WC_Coupon $coupon Coupon object.
     */
    public function save_apply_after_tax_checkbox( int $post_id, \WC_Coupon $coupon ): void {
        // Verify nonce for security
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
            return;
        }
        
        $apply_after_tax = isset( $_POST['tpc_apply_after_tax'] ) ? 'yes' : 'no';
        $coupon->update_meta_data( 'tpc_apply_after_tax', $apply_after_tax );
        $coupon->save();
    }

    /**
     * Apply fixed-cart coupons after tax: convert the gross amount into the correct net discount,
     * then hand that to WC so that tax is re-applied correctly.
     *
     * @param float      $discount           Current discount amount for this item.
     * @param float      $discounting_amount The original line total being discounted.
     * @param array      $cart_item          Cart item data.
     * @param bool       $single             Single item flag.
     * @param \WC_Coupon $coupon             Coupon object.
     * @return float     Modified discount.
     */
    public function apply_coupon_after_tax(
        float      $discount,
        float      $discounting_amount,
        array      $cart_item,
        bool       $single,
        \WC_Coupon $coupon
    ): float {
        // Only target fixed-cart coupons with our checkbox enabled.
        if ( 'fixed_cart' !== $coupon->get_discount_type() ||
             'yes'       !== $coupon->get_meta( 'tpc_apply_after_tax', true ) ) {
            return $discount;
        }

        static $applied = [];
        $code = $coupon->get_code();

        // Only apply once per coupon code.
        if ( in_array( $code, $applied, true ) ) {
            return 0.0;
        }

        // Sum gross and net totals across all product line items.
        $total_gross = 0.0;
        $total_net   = 0.0;
        foreach ( WC()->cart->get_cart() as $item ) {
            $total_net   += wc_get_price_excluding_tax( $item['data'], [ 'qty' => $item['quantity'] ] );
            $total_gross += wc_get_price_including_tax(   $item['data'], [ 'qty' => $item['quantity'] ] );
        }

        if ( $total_net <= 0 || $total_gross <= 0 ) {
            return 0.0;
        }

        // The gross amount the admin entered (e.g. 150).
        $gross_coupon = floatval( $coupon->get_amount() );

        // Compute the average tax rate across items.
        $avg_tax_rate = ( $total_gross / $total_net ) - 1;

        // Convert the gross coupon into the correct net discount.
        $net_discount = $gross_coupon / ( 1 + $avg_tax_rate );
        $net_discount = round( $net_discount, wc_get_price_decimals() );

        // Mark as applied so we don't double-dip.
        $applied[] = $code;

        return $net_discount;
    }

    /** Public wakeup to satisfy PHP’s magic method requirement. */
    public function __wakeup() {}
}

// Bootstrap the plugin.
add_action( 'plugins_loaded', [ Plugin::class, 'instance' ] );
