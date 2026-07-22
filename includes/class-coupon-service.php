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
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'reset_runtime_state' ), 1 );

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'apply_coupon_after_tax' ), 20, 5 );
		add_filter( 'woocommerce_coupon_discount_amount_html', array( $this, 'adjust_coupon_display_amount' ), 20, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'adjust_cart_coupon_display' ), 20, 3 );

		add_action( 'woocommerce_create_order_coupon_item', array( $this, 'set_correct_coupon_amounts' ), 10, 4 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'store_coupon_net_amounts' ), 10, 2 );

		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'adjust_admin_coupon_display' ), 10, 3 );
		add_filter( 'woocommerce_order_get_total_discount', array( $this, 'adjust_total_discount_for_invoices' ), 10, 2 );
	}

	/**
	 * Reset runtime coupon state before cart totals are calculated.
	 */
	public function reset_runtime_state(): void {
		$this->coupon_runtime_state = array();
	}

	/**
	 * Add the after-tax checkbox to fixed-cart coupons.
	 */
	public function add_apply_after_tax_checkbox(): void {
		\woocommerce_wp_checkbox(
			array(
				'id'          => self::COUPON_META_KEY,
				'label'       => __( 'Apply coupon after tax', 'taxproof-coupons-for-woocommerce' ),
				'description' => __( 'Deduct the fixed coupon amount from the order total including tax, so the advertised gross amount stays constant regardless of VAT rate or customer location.', 'taxproof-coupons-for-woocommerce' ),
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

		$coupon->update_meta_data( self::COUPON_META_KEY, isset( $_POST[ self::COUPON_META_KEY ] ) ? 'yes' : 'no' );
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
	 * @param array      $cart_item          Cart item data.
	 * @param bool       $single             Whether the coupon applies to a single item.
	 * @param \WC_Coupon $coupon             Coupon object.
	 * @return float
	 */
	public function apply_coupon_after_tax(
		float $discount,
		float $discounting_amount,
		array $cart_item,
		bool $single,
		\WC_Coupon $coupon
	): float {
		unset( $single );

		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return $discount;
		}

		$coupon_state = $this->get_coupon_state( $coupon );

		if ( $coupon_state['entered_gross'] <= 0 || $coupon_state['effective_gross'] <= 0 || $coupon_state['remaining_net'] <= 0 ) {
			return 0.0;
		}

		$ratio = $coupon_state['entered_gross'] > 0 ? ( $coupon_state['net'] / $coupon_state['entered_gross'] ) : 0.0;

		if ( $ratio <= 0 ) {
			return 0.0;
		}

		$candidate_discount = max( 0.0, $discount ) * $ratio;
		$discount_cap       = max( 0.0, $discounting_amount );
		$applied_net        = min( $candidate_discount, $coupon_state['remaining_net'], $discount_cap );

		$coupon_state['remaining_net'] = max( 0.0, $coupon_state['remaining_net'] - $applied_net );
		$coupon_state['applied_net']   += $applied_net;

		$this->coupon_runtime_state[ $coupon->get_code() ] = $coupon_state;

		return max( 0.0, $applied_net );
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
	 * @param float                 $discount Incoming discount amount from WooCommerce.
	 * @param \WC_Order             $order    Order object.
	 */
	public function set_correct_coupon_amounts( \WC_Order_Item_Coupon $item, string $code, float $discount, \WC_Order $order ): void {
		unset( $discount, $order );

		$coupon = new \WC_Coupon( $code );

		if ( ! $this->is_tax_proof_coupon( $coupon ) ) {
			return;
		}

		$totals = $this->get_coupon_item_totals_for_code( $code, $coupon, $item );

		$item->set_discount( $totals['net'] );
		$item->set_discount_tax( $totals['tax'] );
		$this->persist_coupon_item_meta( $item, $totals );
	}

	/**
	 * Ensure coupon item totals and meta are persisted on the order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $data  Checkout payload.
	 */
	public function store_coupon_net_amounts( \WC_Order $order, array $data ): void {
		unset( $data );

		if ( $this->synchronize_order_coupon_items( $order ) ) {
			$order->save();
		}
	}

	/**
	 * Adjust coupon display in the admin order screen.
	 *
	 * @param string         $subtotal Formatted subtotal.
	 * @param \WC_Order_Item $item     Order item.
	 * @param \WC_Order      $order    Order object.
	 * @return string
	 */
	public function adjust_admin_coupon_display( string $subtotal, \WC_Order_Item $item, \WC_Order $order ): string {
		if ( ! $item instanceof \WC_Order_Item_Coupon || ! $this->is_tax_proof_coupon_item( $item ) ) {
			return $subtotal;
		}

		$amounts = $this->get_coupon_item_totals( $item );
		$context = array( 'currency' => $order->get_currency() );

		return sprintf(
			'%1$s <small>(%2$s %3$s / %4$s %5$s)</small>',
			'-' . wc_price( $amounts['gross'], $context ),
			esc_html__( 'Net', 'taxproof-coupons-for-woocommerce' ),
			wc_price( $amounts['net'], $context ),
			esc_html__( 'Tax', 'taxproof-coupons-for-woocommerce' ),
			wc_price( $amounts['tax'], $context )
		);
	}

	/**
	 * Return the gross discount total for invoices and document generators.
	 *
	 * @param float     $total_discount Current total discount.
	 * @param \WC_Order $order          Order object.
	 * @return float
	 */
	public function adjust_total_discount_for_invoices( float $total_discount, \WC_Order $order ): float {
		if ( ! $this->order_has_tax_proof_coupons( $order ) ) {
			return $total_discount;
		}

		return $this->get_order_discount_total( $order );
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
		return (float) $order->get_subtotal()
			+ (float) $order->get_total_fees()
			+ (float) $order->get_shipping_total()
			+ (float) $order->get_total_tax()
			- $this->get_order_discount_total( $order );
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

			$totals = $this->get_coupon_item_totals_for_code( $coupon_item->get_code(), $coupon, $coupon_item );
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
		$avg_tax_rate    = $eligible_totals['net'] > 0 ? ( $eligible_totals['gross'] / $eligible_totals['net'] ) - 1 : 0.0;
		$net_discount    = $effective_gross > 0 ? ( $effective_gross / ( 1 + $avg_tax_rate ) ) : 0.0;

		$this->coupon_runtime_state[ $code ] = array(
			'entered_gross'   => $entered_gross,
			'effective_gross' => $effective_gross,
			'eligible_gross'  => $eligible_totals['gross'],
			'avg_tax_rate'    => $avg_tax_rate,
			'net'             => max( 0.0, $net_discount ),
			'remaining_net'   => max( 0.0, $net_discount ),
			'applied_net'     => 0.0,
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
	 * Return the totals that should be written to the order coupon item.
	 *
	 * @param string                      $code   Coupon code.
	 * @param \WC_Coupon                  $coupon Coupon object.
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
			$net          = $coupon_state['net'];
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
