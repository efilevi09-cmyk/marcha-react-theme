<?php
/**
 * Marcha Ballerina React theme bootstrap.
 */
defined( 'ABSPATH' ) || exit;

define( 'MARCHA_THEME_VERSION', '0.1.9' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	register_nav_menus( array( 'primary' => 'תפריט ראשי' ) );
} );

/**
 * Routes rendered natively by WordPress/WooCommerce inside the React shell.
 */
function marcha_is_native_route() {
	if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() ) ) {
		return true;
	}
	return is_page() && ! is_front_page() && ! marcha_is_spa_page();
}

function marcha_is_spa_page() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$spa = array( wc_get_page_id( 'shop' ), wc_get_page_id( 'cart' ) );
	return is_page( $spa );
}

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	$js  = $dir . '/dist/assets/app.js';
	$css = $dir . '/dist/assets/app.css';

	if ( file_exists( $css ) ) {
		wp_enqueue_style( 'marcha-app', $uri . '/dist/assets/app.css', array(), filemtime( $css ) );
	}
	if ( file_exists( $js ) ) {
		wp_enqueue_script( 'marcha-app', $uri . '/dist/assets/app.js', array(), filemtime( $js ), true );
	}

	$config = array(
		'siteUrl'     => home_url( '/' ),
		'restUrl'     => esc_url_raw( rest_url() ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'themeUrl'    => $uri,
		'uploadsUrl'  => wp_get_upload_dir()['baseurl'],
		'shopUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
		'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
		'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		'accountUrl'  => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '',
		'isNative'    => marcha_is_native_route(),
		'isLoggedIn'  => is_user_logged_in(),
	);
	wp_add_inline_script( 'marcha-app', 'window.MARCHA=' . wp_json_encode( $config ) . ';', 'before' );
}, 20 );

// Load as ES module.
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( 'marcha-app' === $handle ) {
		return str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 3 );

// Fonts (same files uploaded in Elementor).
add_action( 'wp_head', function () {
	$base = wp_get_upload_dir()['baseurl'];
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . '/2023/09/ploni-regular-aaa.woff2' ) );
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . '/2023/09/ploni-bold-aaa-1.woff2' ) );
	echo '<style>@font-face{font-family:"ploni";font-weight:400;font-display:swap;src:url("' . esc_url( $base . '/2023/09/ploni-regular-aaa.woff2' ) . '") format("woff2")}'
		. '@font-face{font-family:"ploni";font-weight:700;font-display:swap;src:url("' . esc_url( $base . '/2023/09/ploni-bold-aaa-1.woff2' ) . '") format("woff2")}'
		. '@font-face{font-family:"E111Viva";font-display:swap;src:url("' . esc_url( $base . '/2025/03/E111Viva-1.ttf' ) . '") format("truetype")}</style>' . "\n";
}, 1 );

// Remove Elementor/Woo front-end bloat on SPA routes (native checkout keeps Woo styles).
add_action( 'wp_enqueue_scripts', function () {
	if ( marcha_is_native_route() ) {
		return;
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );

/**
 * Always render through the React shell, even while Elementor / Elementor Pro
 * theme-builder templates or page templates are still installed.
 */
add_filter( 'template_include', function () {
	return get_template_directory() . '/index.php';
}, PHP_INT_MAX );

/**
 * Safety net: some plugins (Elementor Pro theme builder, Canvas templates) replace
 * the template after our template_include filter. Render the shell directly instead.
 */
add_action( 'template_redirect', function () {
	if ( is_feed() || is_embed() || is_robots() || is_trackback() || is_favicon() ) {
		return;
	}
	if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && 'HEAD' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		// Fall through to the same shell; handled inside the template.
	}
	include get_template_directory() . '/index.php';
	exit;
}, PHP_INT_MAX );

require_once get_template_directory() . '/inc/updater.php';
