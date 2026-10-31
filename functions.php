<?php
/**
 * Marcha Ballerina React theme bootstrap.
 */
defined( 'ABSPATH' ) || exit;

define( 'MARCHA_THEME_VERSION', '0.1.21' );

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
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}
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
	// Printed as inert JSON so optimisation plugins (WP Rocket delay-JS) cannot defer or rewrite it.
	add_action( 'wp_print_scripts', function () use ( $config ) {
		echo '<script type="application/json" id="marcha-config">' . wp_json_encode( $config ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}, 1 );
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
 *
 * Priority 1000 on purpose: the Shabbat plugin (Shamor) swaps in its own block
 * template through this same filter at 9999, and it has to win.
 */
add_filter( 'template_include', function () {
	return get_template_directory() . '/index.php';
}, 1000 );

/**
 * Is the Shabbat plugin closing the shop for this visitor right now?
 *
 * Shamor decides per visitor (it geolocates the IP for local candle-lighting and
 * havdalah times) inside move_out_of_site(): it hands back a template path while
 * the site is closed and the value it was given while it is open. The answer is
 * cached for the request because asking also sends the 503 headers.
 */
function marcha_shabbat_closed() {
	static $closed = null;
	if ( null !== $closed ) {
		return $closed;
	}
	global $shamor;
	$closed = is_object( $shamor ) && method_exists( $shamor, 'move_out_of_site' )
		&& false !== $shamor->move_out_of_site( false );
	return $closed;
}

/**
 * Safety net: some plugins (Elementor Pro theme builder, Canvas templates) replace
 * the template after our template_include filter. Render the shell directly instead.
 *
 * The template chain is resolved here rather than hard-coding the shell, so a plugin
 * that legitimately takes the page over - the Shabbat block above all - still does.
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
	/** This filter is what WordPress itself would apply a moment later. */
	include apply_filters( 'template_include', get_template_directory() . '/index.php' );
	exit;
}, PHP_INT_MAX );

/**
 * The shop front is a single-page app, so blocking the HTML alone would still leave
 * the Store API open to a tab that was already loaded. Cart changes and checkout are
 * refused while the shop is closed; reading the catalogue stays allowed.
 */
add_filter(
	'rest_pre_dispatch',
	function ( $result, $server, $request ) {
		if ( 'GET' === $request->get_method() || 0 !== strpos( $request->get_route(), '/wc/store/' ) ) {
			return $result;
		}
		if ( ! marcha_shabbat_closed() ) {
			return $result;
		}
		return new WP_Error(
			'marcha_shabbat',
			'החנות סגורה בשבת ובחג. נשמח לראותך במוצאי שבת.',
			array( 'status' => 503 )
		);
	},
	10,
	3
);

require_once get_template_directory() . '/inc/updater.php';
require_once get_template_directory() . '/inc/reviews.php';
require_once get_template_directory() . '/inc/instagram.php';
require_once get_template_directory() . '/inc/bundle.php';
require_once get_template_directory() . '/inc/catalog.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/media.php';

/**
 * WP Rocket: never minify/combine/delay the app bundle. It is already minified and versioned,
 * and Rocket's own copies (wp-content/cache/min) kept serving stale builds to guests.
 */
$marcha_rocket_skip = function ( $list ) {
	$list   = (array) $list;
	$list[] = '/wp-content/themes/marcha-react-theme/dist/assets/(.*)';
	return $list;
};
foreach ( array( 'rocket_exclude_css', 'rocket_exclude_js', 'rocket_exclude_defer_js', 'rocket_delay_js_exclusions', 'rocket_exclude_async_css', 'rocket_rucss_external_exclusions' ) as $marcha_hook ) {
	add_filter( $marcha_hook, $marcha_rocket_skip );
}

/**
 * Checkout order review: show the product thumbnail next to the name so every item reads as one compact row.
 */
add_filter(
	'woocommerce_cart_item_name',
	function ( $name, $cart_item ) {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return $name;
		}
		$product = $cart_item['data'];
		$thumb   = $product ? $product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'mc-item__img', 'loading' => 'lazy' ) ) : '';
		return '<span class="mc-item">' . $thumb . '<span class="mc-item__name">' . wp_strip_all_tags( $name ) . '</span></span>';
	},
	10,
	2
);
