<?php
/**
 * "3 pairs for 300 ₪" bundle. Every complete group of three pairs costs 300 ₪ in total.
 * Applied as a negative cart fee, so it shows in the cart, checkout and Store API totals.
 * Skipped automatically while the Discount Rules plugin is active (it applies the same offer).
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

add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {
		if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || ! marcha_bundle_enabled() ) {
			return;
		}
		$prices = array();
		foreach ( $cart->get_cart() as $item ) {
			if ( ! marcha_product_in_bundle( $item['data'] ) ) {
				continue; // Boots pay full price and do not make up a group of three.
			}
			$unit = (float) wc_get_price_to_display( $item['data'] );
			for ( $i = 0; $i < (int) $item['quantity']; $i++ ) {
				$prices[] = $unit;
			}
		}
		$groups = intdiv( count( $prices ), MARCHA_BUNDLE_QTY );
		if ( $groups < 1 ) {
			return;
		}
		rsort( $prices ); // The most expensive pairs go into the bundles.
		$covered  = array_sum( array_slice( $prices, 0, $groups * MARCHA_BUNDLE_QTY ) );
		$discount = $covered - $groups * MARCHA_BUNDLE_TOTAL;
		if ( $discount > 0 ) {
			// No colon in the name: WooCommerce appends one when it prints this as a
			// totals row in the order emails, and two colons read badly.
			$cart->add_fee( sprintf( 'הנחת מבצע %d נעליים ב-%d ₪', MARCHA_BUNDLE_QTY, MARCHA_BUNDLE_TOTAL ), -$discount, false );
		}
	}
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
