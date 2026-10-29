<?php
/**
 * Admin-only sideload: pull an image into the media library by URL.
 *
 * Used for artwork generated outside WordPress (hero banners, category art). The server
 * fetches the file, so nothing has to travel through the browser.
 *
 * POST marcha/v1/media/sideload {url, filename}
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

function marcha_rest_sideload_media( WP_REST_Request $req ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$url = esc_url_raw( (string) $req->get_param( 'url' ) );
	if ( ! $url || ! wp_http_validate_url( $url ) ) {
		return new WP_Error( 'bad_url', 'כתובת לא תקינה', array( 'status' => 400 ) );
	}
	$name = sanitize_file_name( (string) $req->get_param( 'filename' ) );
	if ( ! $name ) {
		$name = 'marcha-' . gmdate( 'Ymd-His' ) . '.jpg';
	}

	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return new WP_Error( 'download', $tmp->get_error_message(), array( 'status' => 502 ) );
	}
	$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $name );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return new WP_Error( 'sideload', $id->get_error_message(), array( 'status' => 500 ) );
	}
	$src = wp_get_attachment_image_src( $id, 'full' );
	return array(
		'id'   => (int) $id,
		'url'  => $src ? $src[0] : wp_get_attachment_url( $id ),
		'w'    => $src ? $src[1] : 0,
		'h'    => $src ? $src[2] : 0,
		'path' => str_replace( wp_get_upload_dir()['baseurl'] . '/', '', (string) wp_get_attachment_url( $id ) ),
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route( 'marcha/v1', '/media/sideload', array(
			'methods'             => 'POST',
			'callback'            => 'marcha_rest_sideload_media',
			'permission_callback' => function () {
				return current_user_can( 'upload_files' );
			},
		) );
	}
);
