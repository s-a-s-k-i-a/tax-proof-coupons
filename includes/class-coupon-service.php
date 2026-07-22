<?php
/**
 * Core coupon service.
 *
 * @package TaxProofCouponsForWooCommerce
 */

namespace STstudio\TaxProofCouponsForWooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encapsulates the tax-proof fixed-cart coupon behavior.
 */
final class Coupon_Service {
	/**
	 * Coupon-level meta key for enabling after-tax behavior.
	 */
	private const COUPON_META_KEY = 'tpc_apply_after_tax';

	/**
	 * Order item meta key indicating the coupon has been processed by this plugin.
	 */
	private const ITEM_META_PROCESSED = '_tpc_applied_after_tax';

	/**
	 * Order item meta key storing the gross coupon amount.
	 */
	private const ITEM_META_GROSS = '_tpc_gross_discount';

	/**
	 * Order item meta key storing the net coupon amount.
	 */
	private const ITEM_META_NET = '_tpc_net_discount';

	/**
	 * Order item meta key storing the coupon tax component.
	 */
	private const ITEM_META_TAX = '_tpc_tax_component';

	/**
	 * Runtime coupon state for the current totals calculation.
	 *
	 * @var array<string, array<string, float>>
	 */
	private array $coupon_runtime_state = array();

	/**
	 * Register WooCommerce hooks.
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_coupon_options', array( $this, 'add_apply_after_tax_checkbox' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_apply_after_tax_checkbox' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_coupon_admin_script' ) );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'reset_runtime_state' ), 1 );

		add_filter( 'woocommerce_coupon_get_items_to_apply', array( $this, 'prepare_coupon_distribution' ), 10, 3 );
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'apply_coupon_after_tax' ), 20, 5 );
		add_filter( 'woocommerce_coupon_discount_amount_html', array( $this, 'adjust_coupon_display_amount' ), 20, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'adjust_cart_coupon_display' ), 20, 3 );

		add_action( 'woocommerce_checkout_create_order_coupon_item', array( $this, 'set_correct_coupon_amounts' ), 10, 4 );
	}

	/**
	 * Reset runtime coupon state before cart totals are calculated.
	 */
	public function reset_runtime_state(): void {
		$this->coupon_runtime_state = array();
	}

	/**
	 * Reset one coupon immediately before WooCommerce starts its distribution.
	 *
	 * This also covers totals runs started directly by compatibility plugins, where
	 * `woocommerce_before_calculate_totals` is not fired again.
	 *
	 * @param array      $items     Eligible discount items.
	 * @param \WC_Coupon $coupon    Coupon being distributed.
	 * @param mixed      $discounts WooCommerce discounts instance.
	 * @return array
	 */
	public function prepare_coupon_distribution( array $items, \WC_Coupon $coupon, $discounts ): array {
		unset( $discounts );

		if ( $this->is_tax_proof_coupon( $coupon ) ) {
			unset( $this->coupon_runtime_state[ $coupon->get_code() ] );
		}

		return $items;
	}

	/**
	 * Load the coupon-type state controller only in the native coupon editor.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_coupon_admin_script( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'shop_coupon' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'taxproof-coupons-coupon-admin',
			plugins_url( 'assets/js/admin-coupon.js', dirname( __DIR__ ) . '/tax-proof-coupons-plugin.php' ),
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);
	}

	/**
	 * Add the after-tax checkbox with a state matching the saved coupon type.
	 */
	public function add_apply_after_tax_checkbox(): void {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$coupon                  = new \WC_Coupon( $post->ID );
		$is_supported            = 'fixed_cart' === $coupon->get_discount_type( 'edit' );
		$supported_description   = __( 'Deduct the fixed coupon amount from the order total including tax, so the advertised gross amount stays constant regardless of VAT rate or customer location.', 'taxproof-coupons-for-woocommerce' );
		$unsupported_description = __( 'This option is available only for fixed cart discount coupons. The selected coupon type is not currently supported.', 'taxproof-coupons-for-woocommerce' );
		$description             = $is_supported ? $supported_description : $unsupported_description;
		$custom_attributes       = array(
			'aria-describedby'             => self::COUPON_META_KEY . '_description',
			'aria-disabled'                => $is_supported ? 'false' : 'true',
			'data-supported-description'   => $supported_description,
			'data-unsupported-description' => $unsupported_description,
		);

		if ( ! $is_supported ) {
			$custom_attributes['disabled'] = 'disabled';
		}

		\woocommerce_wp_checkbox(
			array(
				'id'                => self::COUPON_META_KEY,
				'label'             => __( 'Apply coupon after tax', 'taxproof-coupons-for-woocommerce' ),
				'description'       => sprintf(
					'<span id="%1$s">%2$s</span>',
					esc_attr( self::COUPON_META_KEY . '_description' ),
					esc_html( $description )
				),
				'value'             => $is_supported ? $coupon->get_meta( self::COUPON_META_KEY, true ) : 'no',
				'custom_attributes' => $custom_attributes,
			)
		);
	}

	/**
	 * Save the after-tax checkbox value.
	 *
	 * @param int        $post_id Coupon post ID.
	 * @param \WC_Coupon $coupon  Coupon object.
	 */
	public function save_apply_after_tax_checkbox( int $post_id, \WC_Coupon $coupon ): void {
		unset( $post_id );

		$nonce = '';

		if ( isset( $_POST['woocommerce_meta_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		}

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) {
			return;
		}

		$requested_value = '';

		if ( isset( $_POST[ self::COUPON_META_KEY ] ) && is_string( $_POST[ self::COUPON_META_KEY ] ) ) {
			$requested_value = sanitize_text_field( wp_unslash( $_POST[ self::COUPON_META_KEY ] ) );
		}

		if ( 'fixed_cart' === $coupon->get_discount_type() && 'yes' === $requested_value ) {
			$coupon->update_meta_data( self::COUPON_META_KEY, 'yes' );
		} else {
			$coupon->delete_meta_data( self::COUPON_META_KEY );
		}

		$coupon->save();
	}

	/**
	 * Apply a fixed-cart coupon as a gross amount.
	 *
	 * WooCommerce distributes fixed-cart coupons across eligible items first. The plugin
	 * converts each incoming gross share into the net discount WooCommerce expects for
	 * the current line item and keeps track of the actually applied gross remainder.
	 *
	 * @param float      $discount           Current per-line discount amount.
	 * @param float      $discounting_amount Current net amount that may be discounted.
	 * @param mixed      $cart_item          Cart item data.
	 * @param bool       $single             Whether the coupon applies to a single item.
	 * @param \WC_Coupon $coupon             Coupon object.
	 * @return float
	 */
	public function apply_coupon_after_tax(
		float $discount,
		float $discounting_amount,
		$cart_item,
		bool $single,
		\WC_Coupon $coupon
	): float {
		unset( $single );

		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return $discount;
		}

		// Fixed-cart amounts are already gross when catalog prices include tax.
		if ( wc_prices_include_tax() ) {
			return $discount;
		}

		$coupon_state = $this->get_coupon_state( $coupon );

		if ( $coupon_state['entered_gross'] <= 0 || $coupon_state['effective_gross'] <= 0 || $coupon_state['remaining_gross'] <= 0 ) {
			return 0.0;
		}

		$allocation = Gross_Discount_Allocator::allocate(
			$discount,
			$discounting_amount,
			$this->get_cart_item_tax_factor( $cart_item, $discounting_amount ),
			$coupon_state['remaining_gross']
		);

		$coupon_state['remaining_gross'] = $allocation['remaining_gross'];
		$coupon_state['applied_net']    += $allocation['net'];
		$coupon_state['applied_gross']  += $allocation['gross'];

		$this->coupon_runtime_state[ $coupon->get_code() ] = $coupon_state;

		return max( 0.0, $allocation['net'] );
	}

	/**
	 * Show the effective gross amount for tax-proof coupons.
	 *
	 * @param string     $discount_amount_html Current HTML.
	 * @param \WC_Coupon $coupon               Coupon object.
	 * @return string
	 */
	public function adjust_coupon_display_amount( string $discount_amount_html, \WC_Coupon $coupon ): string {
		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return $discount_amount_html;
		}

		return '-' . wc_price( $this->get_effective_coupon_gross_amount( $coupon ) );
	}

	/**
	 * Keep the gross coupon amount visible in cart and checkout totals.
	 *
	 * @param string     $coupon_html          Current HTML.
	 * @param \WC_Coupon $coupon               Coupon object.
	 * @param string     $discount_amount_html Current formatted discount HTML.
	 * @return string
	 */
	public function adjust_cart_coupon_display( string $coupon_html, \WC_Coupon $coupon, string $discount_amount_html ): string {
		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return $coupon_html;
		}

		return str_replace( $discount_amount_html, '-' . wc_price( $this->get_effective_coupon_gross_amount( $coupon ) ), $coupon_html );
	}

	/**
	 * Synchronize coupon item totals when WooCommerce creates the order coupon item.
	 *
	 * @param \WC_Order_Item_Coupon $item     Coupon order item.
	 * @param string                $code     Coupon code.
	 * @param \WC_Coupon            $coupon   Coupon object.
	 * @param \WC_Order             $order    Order object.
	 */
	public function set_correct_coupon_amounts( \WC_Order_Item_Coupon $item, string $code, \WC_Coupon $coupon, \WC_Order $order ): void {
		unset( $order );

		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return;
		}

		$totals = $this->get_coupon_item_totals_for_code( $code, $coupon, $item );

		$item->set_discount( $totals['net'] );
		$item->set_discount_tax( $totals['tax'] );
		$this->persist_coupon_item_meta( $item, $totals );
	}

	/**
	 * Return whether the coupon is a tax-proof fixed-cart coupon.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return bool
	 */
	public function is_tax_proof_coupon( \WC_Coupon $coupon ): bool {
		return 'fixed_cart' === $coupon->get_discount_type() && 'yes' === $coupon->get_meta( self::COUPON_META_KEY, true );
	}

	/**
	 * Return whether the order contains at least one processed tax-proof coupon.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function order_has_tax_proof_coupons( \WC_Order $order ): bool {
		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
			if ( $coupon_item instanceof \WC_Order_Item_Coupon && $this->is_tax_proof_coupon_item( $coupon_item ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Calculate the expected gross order discount total.
	 *
	 * @param \WC_Order $order Order object.
	 * @return float
	 */
	public function get_order_discount_total( \WC_Order $order ): float {
		$total = 0.0;

		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
			if ( ! $coupon_item instanceof \WC_Order_Item_Coupon ) {
				continue;
			}

			if ( $this->is_tax_proof_coupon_item( $coupon_item ) ) {
				$amounts = $this->get_coupon_item_totals( $coupon_item );
				$total  += $amounts['gross'];
			} else {
				$total += (float) $coupon_item->get_discount() + (float) $coupon_item->get_discount_tax();
			}
		}

		return $total;
	}

	/**
	 * Calculate the tax component of all tax-proof coupons on the order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return float
	 */
	public function get_order_coupon_tax_total( \WC_Order $order ): float {
		$total = 0.0;

		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
			if ( $coupon_item instanceof \WC_Order_Item_Coupon && $this->is_tax_proof_coupon_item( $coupon_item ) ) {
				$amounts = $this->get_coupon_item_totals( $coupon_item );
				$total  += $amounts['tax'];
			}
		}

		return $total;
	}

	/**
	 * Recalculate the order total from persisted order values.
	 *
	 * @param \WC_Order $order Order object.
	 * @return float
	 */
	public function calculate_expected_order_total( \WC_Order $order ): float {
		$line_total = 0.0;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$line_total += (float) $item->get_total();
		}

		$total = $line_total
			+ (float) $order->get_total_fees()
			+ (float) $order->get_shipping_total()
			+ (float) $order->get_total_tax();

		return round( $total, wc_get_price_decimals() );
	}

	/**
	 * Synchronize all processed tax-proof coupon items on the order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function synchronize_order_coupon_items( \WC_Order $order ): bool {
		$updated = false;

		foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
			if ( ! $coupon_item instanceof \WC_Order_Item_Coupon ) {
				continue;
			}

			$coupon = new \WC_Coupon( $coupon_item->get_code() );

			if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
				continue;
			}

			$totals       = $this->get_coupon_item_totals_for_code( $coupon_item->get_code(), $coupon, $coupon_item );
			$item_updated = false;

			if ( ! $this->coupon_item_matches_totals( $coupon_item, $totals ) ) {
				$coupon_item->set_discount( $totals['net'] );
				$coupon_item->set_discount_tax( $totals['tax'] );
				$item_updated = true;
			}

			$item_updated = $this->persist_coupon_item_meta( $coupon_item, $totals ) || $item_updated;

			if ( $item_updated ) {
				$coupon_item->save();
				$updated = true;
			}
		}

		return $updated;
	}

	/**
	 * Return the current runtime state for the coupon.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return array<string, float>
	 */
	private function get_coupon_state( \WC_Coupon $coupon ): array {
		$code = $coupon->get_code();

		if ( isset( $this->coupon_runtime_state[ $code ] ) ) {
			return $this->coupon_runtime_state[ $code ];
		}

		$eligible_totals = $this->get_coupon_eligible_cart_totals( $coupon );
		$entered_gross   = max( 0.0, (float) $coupon->get_amount() );
		$effective_gross = min( $entered_gross, $eligible_totals['gross'] );

		$this->coupon_runtime_state[ $code ] = array(
			'entered_gross'   => $entered_gross,
			'effective_gross' => $effective_gross,
			'eligible_gross'  => $eligible_totals['gross'],
			'remaining_gross' => $effective_gross,
			'applied_net'     => 0.0,
			'applied_gross'   => 0.0,
		);

		return $this->coupon_runtime_state[ $code ];
	}

	/**
	 * Return the effective gross amount for the coupon in the current cart context.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return float
	 */
	private function get_effective_coupon_gross_amount( \WC_Coupon $coupon ): float {
		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return (float) $coupon->get_amount();
		}

		$cart_totals = $this->get_coupon_totals_from_cart( $coupon->get_code() );

		if ( $cart_totals['gross'] > 0 ) {
			return $cart_totals['gross'];
		}

		$coupon_state = $this->get_coupon_state( $coupon );

		return $coupon_state['effective_gross'];
	}

	/**
	 * Return the total gross and net amount of eligible cart items for the coupon.
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return array<string, float>
	 */
	private function get_coupon_eligible_cart_totals( \WC_Coupon $coupon ): array {
		$gross = 0.0;
		$net   = 0.0;

		if ( ! WC()->cart ) {
			return array(
				'gross' => 0.0,
				'net'   => 0.0,
			);
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! $this->coupon_applies_to_cart_item( $coupon, $cart_item ) ) {
				continue;
			}

			$item_amounts = $this->get_cart_item_amounts( $cart_item );
			$gross       += $item_amounts['gross'];
			$net         += $item_amounts['net'];
		}

		return array(
			'gross' => $gross,
			'net'   => $net,
		);
	}

	/**
	 * Determine whether the coupon may apply to the cart item.
	 *
	 * @param \WC_Coupon $coupon    Coupon object.
	 * @param array      $cart_item Cart item data.
	 * @return bool
	 */
	private function coupon_applies_to_cart_item( \WC_Coupon $coupon, array $cart_item ): bool {
		if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
			return false;
		}

		$quantity = isset( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0.0;
		$product  = $cart_item['data'];
		$price    = (float) $product->get_price();

		if ( $quantity <= 0 || $price <= 0 ) {
			return false;
		}

		return $coupon->is_valid_for_cart() || $coupon->is_valid_for_product( $product, $cart_item );
	}

	/**
	 * Return gross/net totals and the effective tax factor for a cart item.
	 *
	 * @param array $cart_item Cart item data.
	 * @return array<string, float>
	 */
	private function get_cart_item_amounts( array $cart_item ): array {
		if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
			return array(
				'gross'      => 0.0,
				'net'        => 0.0,
				'tax_factor' => 1.0,
			);
		}

		$quantity = isset( $cart_item['quantity'] ) ? max( 1, absint( $cart_item['quantity'] ) ) : 1;
		$product  = $cart_item['data'];
		$net      = (float) wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
		$gross    = (float) wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );

		return array(
			'gross'      => $gross,
			'net'        => $net,
			'tax_factor' => $net > 0 ? ( $gross / $net ) : 1.0,
		);
	}

	/**
	 * Return the gross/net factor for the current WooCommerce discount share.
	 *
	 * @param mixed $cart_item          Cart item data.
	 * @param float $discounting_amount Current net value being discounted.
	 * @return float
	 */
	private function get_cart_item_tax_factor( $cart_item, float $discounting_amount ): float {
		if ( ! is_array( $cart_item ) || empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product || $discounting_amount <= 0 ) {
			return 1.0;
		}

		$gross = (float) wc_get_price_including_tax(
			$cart_item['data'],
			array(
				'price' => $discounting_amount,
				'qty'   => 1,
			)
		);

		return $gross > 0 ? max( 1.0, $gross / $discounting_amount ) : 1.0;
	}

	/**
	 * Return the totals that should be written to the order coupon item.
	 *
	 * @param string                     $code   Coupon code.
	 * @param \WC_Coupon                 $coupon Coupon object.
	 * @param \WC_Order_Item_Coupon|null $item   Coupon item.
	 * @return array<string, float>
	 */
	private function get_coupon_item_totals_for_code( string $code, \WC_Coupon $coupon, ?\WC_Order_Item_Coupon $item = null ): array {
		$gross = 0.0;
		$net   = 0.0;
		$tax   = 0.0;

		$cart_totals = $this->get_coupon_totals_from_cart( $code );

		if ( $cart_totals['gross'] > 0 ) {
			$gross = $cart_totals['gross'];
			$net   = $cart_totals['net'];
			$tax   = $cart_totals['tax'];
		} elseif ( WC()->cart ) {
			$coupon_state = $this->get_coupon_state( $coupon );
			$gross        = $coupon_state['effective_gross'];
			$net          = $coupon_state['applied_net'];
			$tax          = max( 0.0, $gross - $net );
		}

		if ( $item ) {
			$item_amounts = $this->get_coupon_item_totals( $item );

			if ( $gross <= 0 ) {
				$gross = $item_amounts['gross'];
			}

			if ( $net <= 0 ) {
				$net = $item_amounts['net'];
			}
		}

		if ( $gross <= 0 ) {
			$gross = max( 0.0, (float) $coupon->get_amount() );
		}

		if ( $net <= 0 ) {
			$net = min( $gross, max( 0.0, $gross - $tax ) );
		}

		$tax = max( 0.0, $gross - $net );

		return array(
			'gross' => $gross,
			'net'   => $net,
			'tax'   => $tax,
		);
	}

	/**
	 * Read the currently calculated coupon totals from the WooCommerce cart.
	 *
	 * @param string $code Coupon code.
	 * @return array<string, float>
	 */
	private function get_coupon_totals_from_cart( string $code ): array {
		if ( ! WC()->cart ) {
			return array(
				'gross' => 0.0,
				'net'   => 0.0,
				'tax'   => 0.0,
			);
		}

		$net = (float) WC()->cart->get_coupon_discount_amount( $code, true );
		$tax = (float) WC()->cart->get_coupon_discount_tax_amount( $code );

		return array(
			'gross' => max( 0.0, $net + $tax ),
			'net'   => max( 0.0, $net ),
			'tax'   => max( 0.0, $tax ),
		);
	}

	/**
	 * Read persisted or runtime-aware totals from a coupon order item.
	 *
	 * @param \WC_Order_Item_Coupon $item Coupon order item.
	 * @return array<string, float>
	 */
	private function get_coupon_item_totals( \WC_Order_Item_Coupon $item ): array {
		$gross = (float) $item->get_meta( self::ITEM_META_GROSS, true );
		$net   = (float) $item->get_meta( self::ITEM_META_NET, true );
		$tax   = (float) $item->get_meta( self::ITEM_META_TAX, true );

		if ( $gross <= 0 ) {
			$gross = (float) $item->get_discount() + (float) $item->get_discount_tax();
		}

		if ( $net <= 0 ) {
			$net = (float) $item->get_discount();
		}

		if ( $tax <= 0 ) {
			$tax = max( 0.0, $gross - $net );
		}

		return array(
			'gross' => $gross,
			'net'   => $net,
			'tax'   => $tax,
		);
	}

	/**
	 * Persist plugin-specific coupon item meta and return whether anything changed.
	 *
	 * @param \WC_Order_Item_Coupon $item   Coupon order item.
	 * @param array<string, float>  $totals Coupon totals.
	 * @return bool
	 */
	private function persist_coupon_item_meta( \WC_Order_Item_Coupon $item, array $totals ): bool {
		$precision = wc_get_price_decimals() + 4;
		$changed   = false;

		$meta_updates = array(
			self::ITEM_META_GROSS     => wc_format_decimal( $totals['gross'], $precision ),
			self::ITEM_META_NET       => wc_format_decimal( $totals['net'], $precision ),
			self::ITEM_META_TAX       => wc_format_decimal( $totals['tax'], $precision ),
			self::ITEM_META_PROCESSED => 'yes',
		);

		foreach ( $meta_updates as $meta_key => $meta_value ) {
			if ( (string) $item->get_meta( $meta_key, true ) !== (string) $meta_value ) {
				$item->update_meta_data( $meta_key, $meta_value );
				$changed = true;
			}
		}

		return $changed;
	}

	/**
	 * Determine whether the coupon item already matches the expected totals.
	 *
	 * @param \WC_Order_Item_Coupon $item   Coupon order item.
	 * @param array<string, float>  $totals Expected totals.
	 * @return bool
	 */
	private function coupon_item_matches_totals( \WC_Order_Item_Coupon $item, array $totals ): bool {
		return abs( (float) $item->get_discount() - $totals['net'] ) < 0.0001
			&& abs( (float) $item->get_discount_tax() - $totals['tax'] ) < 0.0001;
	}

	/**
	 * Return whether the order item is a processed tax-proof coupon item.
	 *
	 * @param \WC_Order_Item_Coupon $item Coupon order item.
	 * @return bool
	 */
	private function is_tax_proof_coupon_item( \WC_Order_Item_Coupon $item ): bool {
		if ( 'yes' === $item->get_meta( self::ITEM_META_PROCESSED, true ) ) {
			return true;
		}

		$coupon = new \WC_Coupon( $item->get_code() );

		return $this->is_tax_proof_coupon( $coupon );
	}
}
