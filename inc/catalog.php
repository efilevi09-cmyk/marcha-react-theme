<?php
/**
 * Catalogue data the SPA depends on:
 *  - the `zeva` (colour) taxonomy, registered here so colour filtering keeps working
 *    even when the plugin that originally created it (JetEngine) is switched off;
 *  - units sold per product, exposed on the Store API as extensions.marcha.sold.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		if ( taxonomy_exists( 'zeva' ) ) {
			return;
		}
		register_taxonomy(
			'zeva',
			array( 'product' ),
			array(
				'labels'            => array( 'name' => 'צבע', 'singular_name' => 'צבע' ),
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'zeva',
				'rewrite'           => array( 'slug' => 'zeva' ),
			)
		);
	},
	30
);

add_action(
	'woocommerce_init',
	function () {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema::IDENTIFIER,
				'namespace'       => 'marcha',
				'data_callback'   => function ( $product ) {
					return array( 'sold' => (int) $product->get_total_sales() );
				},
				'schema_callback' => function () {
					return array(
						'sold' => array(
							'description' => 'Units sold of this product.',
							'type'        => 'integer',
							'readonly'    => true,
						),
					);
				},
				'schema_type'     => ARRAY_A,
			)
		);
	}
);

/* Checkout: no "company name" and no postcode (billing, shipping and account addresses). */
add_filter(
	'woocommerce_default_address_fields',
	function ( $fields ) {
		unset( $fields['company'], $fields['postcode'] );
		return $fields;
	}
);
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		unset(
			$fields['billing']['billing_company'],
			$fields['shipping']['shipping_company'],
			$fields['billing']['billing_postcode'],
			$fields['shipping']['shipping_postcode']
		);
		return $fields;
	},
	99
);
/* Nothing asks for a postcode any more, so it must not hold an order up either. */
add_filter( 'woocommerce_billing_fields', function ( $fields ) {
	unset( $fields['billing_postcode'] );
	return $fields;
}, 99 );
add_filter( 'woocommerce_shipping_fields', function ( $fields ) {
	unset( $fields['shipping_postcode'] );
	return $fields;
}, 99 );

/**
 * Local pickup costs nothing, and WooCommerce prints no price for a free method,
 * so shoppers could not tell. Say it in words next to the option.
 */
add_filter(
	'woocommerce_cart_shipping_method_full_label',
	function ( $label, $method ) {
		if ( 'local_pickup' === $method->get_method_id() && 0.0 === (float) $method->get_cost() ) {
			$label .= ' <span class="mc-free">ללא עלות</span>';
		}
		return $label;
	},
	10,
	2
);
