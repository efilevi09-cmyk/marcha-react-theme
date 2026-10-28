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
			$cart->add_fee( sprintf( 'הנחת מבצע: %d נעליים ב-%d ₪', MARCHA_BUNDLE_QTY, MARCHA_BUNDLE_TOTAL ), -$discount, false );
		}
	}
);
