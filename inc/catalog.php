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
