<?php
/**
 * Legacy page redirects.
 *
 * The site carries a set of English pages left over from an earlier version — Contact,
 * About Us, FAQ, Shipping, Return Policy, Terms — plus two orphan WooCommerce pages.
 * Each one duplicates a Hebrew page that says the same thing, so search engines split the
 * site's authority between the two and shoppers sometimes land on the dead English copy.
 *
 * These send the old URL to its Hebrew twin with a 301, which passes the accumulated
 * ranking on instead of throwing it away. Pages that must keep working but do not belong
 * in search results (the e-mail preference page, the Shabbat notices) are marked noindex
 * rather than redirected.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

/**
 * Old page ID => new page ID, or a callable returning the destination URL.
 *
 * Keyed by ID rather than by path so a slug change cannot silently break a redirect.
 */
function marcha_legacy_redirects() {
	return array(
		285    => 239,    // contact            -> צרי קשר
		179    => 128589, // about-us           -> אודות
		229    => 138094, // faq                -> שאלות נפוצות
		101512 => 102939, // return-policy      -> החלפות והחזרות
		101520 => 102939, // shipping (empty)   -> החלפות והחזרות
		311    => 128580, // terms-conditions   -> תנאי שימוש
		101426 => 'cart',    // orphan Cart page    -> the live WooCommerce cart
		115    => 'account', // orphan account page -> the live WooCommerce account page
	);
}

/** Pages that stay reachable but are kept out of search results. */
function marcha_noindex_pages() {
	return array(
		132465, // Let's Keep In Touch — e-mail preference page reached from newsletters
		128559, // שבת — Shabbat notice
		133039, // שבת — second Shabbat notice
	);
}

/** Resolves a redirect target to a URL, or an empty string when it cannot be resolved. */
function marcha_redirect_target( $target ) {
	if ( 'cart' === $target ) {
		return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
	}
	if ( 'account' === $target ) {
		return function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'myaccount' ) : '';
	}
	$page = get_post( (int) $target );
	return ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';
}

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || ! is_page() ) {
			return;
		}
		$id  = (int) get_queried_object_id();
		$map = marcha_legacy_redirects();
		if ( ! isset( $map[ $id ] ) ) {
			return;
		}
		$url = marcha_redirect_target( $map[ $id ] );
		// Never redirect onto the page being requested, and never onto a page that is gone.
		if ( ! $url || untrailingslashit( $url ) === untrailingslashit( get_permalink( $id ) ) ) {
			return;
		}
		wp_safe_redirect( $url, 301 );
		exit;
	},
	1
);

/** Keep the retired pages out of the index even while they stay reachable. */
add_filter(
	'wpseo_robots',
	function ( $robots ) {
		if ( is_page( marcha_noindex_pages() ) ) {
			return 'noindex, follow';
		}
		return $robots;
	}
);

add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) || ! is_page( marcha_noindex_pages() ) ) {
			return;
		}
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
	},
	1
);
