<?php
/**
 * "3 pairs for 300 ₪" bundle. Every complete group of three pairs costs 300 ₪ in total.
 * Applied as a negative cart fee, so it shows in the cart, checkout and Store API totals.
 * Skipped automatically while the Discount Rules plugin is active (it applies the same offer).
 *
 * Coupons cannot be combined with the offer. A coupon is worked out from the full product
 * price, not from the 300 ₪ the offer charges, so stacking the two subtracts the same money
 * twice: three pairs at 329 ₪ with a 35% code come to 987 − 345 − 687 = −45 ₪, which
 * WooCommerce floors at zero and hands over for free. Any percentage does the same damage on
 * a smaller scale, so the coupon is refused outright whenever the cart makes up a group.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

const MARCHA_BUNDLE_QTY   = 3;
const MARCHA_BUNDLE_TOTAL = 300;

/**
 * Categories the offer does not cover. Boots are sold at full price.
 *
 * @return string[] Product category slugs.
 */
function marcha_bundle_excluded_categories() {
	return (array) apply_filters( 'marcha_bundle_excluded_categories', array( 'boots', 'winter' ) );
}

/**
 * Does this product count towards the 3-for-300 offer?
 *
 * A variation carries no categories of its own, so the parent is what gets checked.
 *
 * @param WC_Product|mixed $product Product or variation.
 * @return bool
 */
function marcha_product_in_bundle( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	$excluded = marcha_bundle_excluded_categories();
	if ( ! $excluded ) {
		return true;
	}
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

	return ! has_term( $excluded, 'product_cat', $id );
}

function marcha_bundle_enabled() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$by_plugin = is_plugin_active( 'woo-discount-rules/woo-discount-rules.php' ) || is_plugin_active( 'woo-discount-rules-pro/woo-discount-rules-pro.php' );
	return (bool) apply_filters( 'marcha_bundle_enabled', ! $by_plugin );
}

/**
 * One entry per pair in the cart that the offer covers, at the price it displays for.
 *
 * @param WC_Cart $cart Cart to read.
 * @return float[] Unit prices, one per pair.
 */
function marcha_bundle_prices( $cart ) {
	$prices = array();
	if ( ! $cart instanceof WC_Cart ) {
		return $prices;
	}
	foreach ( $cart->get_cart() as $item ) {
		if ( ! marcha_product_in_bundle( $item['data'] ) ) {
			continue; // Boots pay full price and do not make up a group of three.
		}
		$unit = (float) wc_get_price_to_display( $item['data'] );
		for ( $i = 0; $i < (int) $item['quantity']; $i++ ) {
			$prices[] = $unit;
		}
	}

	return $prices;
}

/**
 * How many complete groups of three the cart makes up.
 *
 * @param WC_Cart|null $cart Cart to read; the session cart when omitted.
 * @return int
 */
function marcha_bundle_groups( $cart = null ) {
	if ( ! $cart instanceof WC_Cart ) {
		$cart = ( function_exists( 'WC' ) && WC()->cart instanceof WC_Cart ) ? WC()->cart : null;
	}
	if ( ! $cart instanceof WC_Cart ) {
		return 0;
	}

	return intdiv( count( marcha_bundle_prices( $cart ) ), MARCHA_BUNDLE_QTY );
}

/** Coupon money already taken off the cart, including its tax share. */
function marcha_bundle_coupon_discount( $cart ) {
	if ( ! $cart instanceof WC_Cart ) {
		return 0.0;
	}
	$net = array_sum( (array) $cart->get_coupon_discount_totals() );
	$tax = array_sum( (array) $cart->get_coupon_discount_tax_totals() );

	return (float) ( $net + $tax );
}

add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {
		if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || ! marcha_bundle_enabled() ) {
			return;
		}
		$prices = marcha_bundle_prices( $cart );
		$groups = intdiv( count( $prices ), MARCHA_BUNDLE_QTY );
		if ( $groups < 1 ) {
			return;
		}
		rsort( $prices ); // The most expensive pairs go into the bundles.
		$covered  = array_sum( array_slice( $prices, 0, $groups * MARCHA_BUNDLE_QTY ) );
		$discount = $covered - $groups * MARCHA_BUNDLE_TOTAL;

		// Coupons are refused below, but one can still be added by hand to an order in the
		// admin. Whatever a coupon already took off is deducted here, so the two discounts
		// can never be charged against the same pairs twice.
		$discount -= marcha_bundle_coupon_discount( $cart );

		if ( $discount > 0 ) {
			// No colon in the name: WooCommerce appends one when it prints this as a
			// totals row in the order emails, and two colons read badly.
			$cart->add_fee( sprintf( 'הנחת מבצע %d נעליים ב-%d ₪', MARCHA_BUNDLE_QTY, MARCHA_BUNDLE_TOTAL ), -$discount, false );
		}
	}
);

/**
 * Refuse any coupon once the cart qualifies for the offer.
 *
 * WooCommerce re-checks every applied coupon on each recalculation, so a code entered while
 * the cart held two pairs is dropped, with this notice, the moment a third is added.
 */
add_filter(
	'woocommerce_coupon_is_valid',
	function ( $valid, $coupon, $discounts = null ) {
		if ( ! $valid || ! marcha_bundle_enabled() ) {
			return $valid;
		}
		// Leave orders built by hand in wp-admin alone; the fee above already protects them.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return $valid;
		}
		if ( marcha_bundle_groups() < 1 ) {
			return $valid;
		}
		throw new Exception(
			sprintf(
				'מבצע %d נעליים ב-%d ₪ כבר חל על ההזמנה, ולא ניתן להוסיף עליו קוד הנחה.',
				MARCHA_BUNDLE_QTY,
				MARCHA_BUNDLE_TOTAL
			)
		);
	},
	10,
	3
);

/**
 * Tell the shop front which cart lines count towards the offer, so the progress bar
 * and the confetti follow the same rule the fee above does.
 */
add_action(
	'woocommerce_init',
	function () {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' )
			|| ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
				'namespace'       => 'marcha',
				'data_callback'   => function ( $item ) {
					return array( 'bundle' => marcha_product_in_bundle( isset( $item['data'] ) ? $item['data'] : null ) );
				},
				'schema_callback' => function () {
					return array(
						'bundle' => array(
							'description' => 'Whether this line counts towards the 3-for-300 offer.',
							'type'        => 'boolean',
							'readonly'    => true,
						),
					);
				},
				'schema_type'     => ARRAY_A,
			)
		);
	}
);
