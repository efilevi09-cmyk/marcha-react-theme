<?php
/**
 * Instagram strip: thumbnails are copied into the media library (Instagram CDN links expire),
 * so the footer feed is fast and never depends on Instagram being reachable.
 *
 * Refresh: POST marcha/v1/instagram/sync {codes:["Cxxxx",...]} (admin only).
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

const MARCHA_IG_OPTION = 'marcha_instagram_feed';

function marcha_ig_fetch_image_url( $code ) {
	$res = wp_remote_get( 'https://www.instagram.com/p/' . rawurlencode( $code ) . '/embed/', array( 'timeout' => 20, 'user-agent' => 'Mozilla/5.0 (compatible; MarchaThemeBot/1.0)' ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	$html = wp_remote_retrieve_body( $res );
	if ( preg_match( '/class="EmbeddedMediaImage"[^>]*src="([^"]+)"/', $html, $m ) || preg_match( '/<meta property="og:image" content="([^"]+)"/', $html, $m ) ) {
		return html_entity_decode( $m[1] );
	}
	return '';
}

function marcha_rest_instagram_sync( WP_REST_Request $req ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$codes = array_slice( array_map( 'sanitize_text_field', (array) $req->get_param( 'codes' ) ), 0, 12 );
	$items = array();
	$log   = array();
	foreach ( $codes as $code ) {
		$src = marcha_ig_fetch_image_url( $code );
		if ( ! $src ) {
			$log[ $code ] = 'no image found';
			continue;
		}
		$tmp = download_url( $src, 30 );
		if ( is_wp_error( $tmp ) ) {
			$log[ $code ] = $tmp->get_error_message();
			continue;
		}
		$id = media_handle_sideload( array( 'name' => 'instagram-' . $code . '.jpg', 'tmp_name' => $tmp ), 0, 'Instagram ' . $code );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			$log[ $code ] = $id->get_error_message();
			continue;
		}
		$items[]       = array( 'code' => $code, 'image' => (int) $id );
		$log[ $code ] = 'ok';
	}
	if ( $items ) {
		update_option( MARCHA_IG_OPTION, array( 'handle' => 'marchaballerina', 'items' => $items ), false );
	}
	return array( 'saved' => count( $items ), 'log' => $log );
}

function marcha_rest_instagram_get() {
	$data  = get_option( MARCHA_IG_OPTION, array( 'handle' => 'marchaballerina', 'items' => array() ) );
	$items = array();
	foreach ( $data['items'] as $it ) {
		$src = wp_get_attachment_image_src( $it['image'], 'medium_large' );
		if ( $src ) {
			$items[] = array( 'url' => 'https://www.instagram.com/p/' . $it['code'] . '/', 'src' => $src[0], 'w' => $src[1], 'h' => $src[2] );
		}
	}
	return new WP_REST_Response( array( 'handle' => $data['handle'], 'items' => $items ) );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route( 'marcha/v1', '/instagram', array( 'methods' => 'GET', 'callback' => 'marcha_rest_instagram_get', 'permission_callback' => '__return_true' ) );
		register_rest_route( 'marcha/v1', '/instagram/sync', array(
			'methods'             => 'POST',
			'callback'            => 'marcha_rest_instagram_sync',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}
);
