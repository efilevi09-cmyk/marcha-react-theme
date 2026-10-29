<?php
/**
 * Reviews REST API: list, submit (moderated) and admin-only import.
 *
 * Reviews are stored as native WooCommerce review comments, so the data stays in WordPress.
 * Reviews whose product no longer exists are attached to the shop page and shown only in the global feed.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

/** Page that holds reviews of products that no longer exist. */
function marcha_reviews_fallback_post() {
	$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
	return $id > 0 ? $id : 0;
}

/** Resolve a product / variation ID to its parent product ID (0 when unknown). */
function marcha_resolve_product_id( $id ) {
	$id   = (int) $id;
	$type = get_post_type( $id );
	if ( 'product' === $type ) {
		return $id;
	}
	if ( 'product_variation' === $type ) {
		return (int) wp_get_post_parent_id( $id );
	}
	return 0;
}

function marcha_format_review( $c ) {
	$images = get_comment_meta( $c->comment_ID, 'marcha_images', true );
	return array(
		'id'       => (int) $c->comment_ID,
		'name'     => $c->comment_author,
		'text'     => wp_strip_all_tags( $c->comment_content ),
		'stars'    => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
		'date'     => mysql2date( 'c', $c->comment_date_gmt, false ),
		'verified' => (bool) get_comment_meta( $c->comment_ID, 'verified', true ),
		'images'   => is_array( $images ) ? array_values( array_map( 'esc_url_raw', $images ) ) : array(),
		'product'  => (int) $c->comment_post_ID,
	);
}

function marcha_reviews_summary( $product_id ) {
	global $wpdb;
	$where = $product_id ? $wpdb->prepare( 'AND c.comment_post_ID = %d', $product_id ) : '';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row( "SELECT COUNT(*) AS n, AVG(m.meta_value) AS avg FROM {$wpdb->comments} c JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = 'rating' WHERE c.comment_type = 'review' AND c.comment_approved = '1' {$where}" );
	return array(
		'count'   => $row ? (int) $row->n : 0,
		'average' => $row && $row->n ? round( (float) $row->avg, 2 ) : 0,
	);
}

function marcha_rest_get_reviews( WP_REST_Request $req ) {
	$product  = (int) $req->get_param( 'product' );
	$page     = max( 1, (int) $req->get_param( 'page' ) );
	$per_page = min( 24, max( 1, (int) ( $req->get_param( 'per_page' ) ?: 6 ) ) );
	$args     = array(
		'type'    => 'review',
		'status'  => 'approve',
		'number'  => $per_page,
		'offset'  => ( $page - 1 ) * $per_page,
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	);
	if ( $product ) {
		$args['post_id'] = $product;
	}
	$items   = array_map( 'marcha_format_review', get_comments( $args ) );
	$summary = marcha_reviews_summary( $product );
	$res     = new WP_REST_Response(
		array(
			'items'   => $items,
			'summary' => $summary,
			'hasMore' => $page * $per_page < $summary['count'],
		)
	);
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}

function marcha_rest_submit_review( WP_REST_Request $req ) {
	// Honeypot: bots fill hidden fields.
	if ( $req->get_param( 'website' ) ) {
		return new WP_REST_Response( array( 'ok' => true ) );
	}
	$product = (int) $req->get_param( 'product' );
	$name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
	$email   = sanitize_email( (string) $req->get_param( 'email' ) );
	$text    = sanitize_textarea_field( (string) $req->get_param( 'text' ) );
	$stars   = (int) $req->get_param( 'stars' );

	if ( 'product' !== get_post_type( $product ) || $stars < 1 || $stars > 5 || mb_strlen( $text ) < 3 || '' === $name || ! is_email( $email ) ) {
		return new WP_Error( 'invalid', 'נא למלא שם, אימייל תקין, דירוג וטקסט.', array( 'status' => 400 ) );
	}
	// Simple rate limit per IP: 3 submissions per hour.
	$key   = 'marcha_rv_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$count = (int) get_transient( $key );
	if ( $count >= 3 ) {
		return new WP_Error( 'rate', 'נשלחו יותר מדי ביקורות, נסי שוב מאוחר יותר.', array( 'status' => 429 ) );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	$id = wp_insert_comment(
		array(
			'comment_post_ID'      => $product,
			'comment_author'       => $name,
			'comment_author_email' => $email,
			'comment_content'      => $text,
			'comment_type'         => 'review',
			'comment_approved'     => 0, // Moderated: appears after approval in WP admin.
			'comment_meta'         => array( 'rating' => $stars ),
		)
	);
	return $id ? new WP_REST_Response( array( 'ok' => true ) ) : new WP_Error( 'fail', 'לא הצלחנו לשמור את הביקורת.', array( 'status' => 500 ) );
}

/**
 * Reads data/reviews.csv shipped with the theme (the export from the old shop).
 *
 * Columns: ID, Product ID, Order ID, Email, Name, Username, Review, Stars, Images, Videos,
 * Status, Verified, Type, Created At. "Rejected" rows are left out; "Pending" rows arrive
 * unapproved so they stay in moderation exactly as they were.
 */
function marcha_reviews_from_file() {
	$file = get_template_directory() . '/data/reviews.csv';
	if ( ! file_exists( $file ) ) {
		return array();
	}
	$fh = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $fh ) {
		return array();
	}
	$head = fgetcsv( $fh );
	$idx  = is_array( $head ) ? array_flip( array_map( 'trim', $head ) ) : array();
	$get  = function ( $row, $key ) use ( $idx ) {
		return isset( $idx[ $key ], $row[ $idx[ $key ] ] ) ? trim( (string) $row[ $idx[ $key ] ] ) : '';
	};
	$rows = array();
	while ( false !== ( $row = fgetcsv( $fh ) ) ) {
		if ( ! is_array( $row ) || count( $row ) < 3 ) {
			continue;
		}
		$status = strtolower( $get( $row, 'Status' ) );
		if ( 'rejected' === $status ) {
			continue;
		}
		$rows[] = array(
			'id'       => (int) $get( $row, 'ID' ),
			'pid'      => (int) $get( $row, 'Product ID' ),
			'name'     => $get( $row, 'Name' ),
			'text'     => $get( $row, 'Review' ),
			'stars'    => (int) $get( $row, 'Stars' ),
			'date'     => $get( $row, 'Created At' ),
			'verified' => 'yes' === strtolower( $get( $row, 'Verified' ) ),
			'images'   => $get( $row, 'Images' ),
			'approved' => 'published' === $status ? 1 : 0,
		);
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $rows;
}

/** Admin-only import: POST {rows:[{id,pid,name,text,stars,date,verified,images,approved}]}, or no body to read data/reviews.csv. */
function marcha_rest_import_reviews( WP_REST_Request $req ) {
	$body     = $req->get_json_params();
	$rows     = isset( $body['rows'] ) && $body['rows'] ? $body['rows'] : marcha_reviews_from_file();
	$fallback = marcha_reviews_fallback_post();
	$out      = array( 'imported' => 0, 'approved' => 0, 'pending' => 0, 'mapped' => 0, 'general' => 0, 'skipped' => 0 );
	$touched  = array();
	foreach ( $rows as $r ) {
		$csv_id = (int) $r['id'];
		$exists = get_comments( array( 'type' => 'review', 'meta_key' => 'marcha_csv_id', 'meta_value' => $csv_id, 'number' => 1, 'status' => 'all', 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $exists ) {
			++$out['skipped'];
			continue;
		}
		$pid = marcha_resolve_product_id( $r['pid'] );
		$gen = 0 === $pid;
		$pid = $gen ? $fallback : $pid;
		if ( ! $pid ) {
			++$out['skipped'];
			continue;
		}
		$date = gmdate( 'Y-m-d H:i:s', strtotime( $r['date'] ) );
		$meta = array( 'rating' => (int) $r['stars'], 'marcha_csv_id' => $csv_id, 'verified' => ! empty( $r['verified'] ) ? 1 : 0 );
		$imgs = array_filter( array_map( 'esc_url_raw', array_filter( array_map( 'trim', explode( ',', (string) $r['images'] ) ) ) ) );
		if ( $imgs ) {
			$meta['marcha_images'] = array_values( $imgs );
		}
		$approved = array_key_exists( 'approved', $r ) ? (int) $r['approved'] : 1;
		wp_insert_comment(
			array(
				'comment_post_ID'  => $pid,
				'comment_author'   => sanitize_text_field( $r['name'] ),
				'comment_content'  => sanitize_textarea_field( $r['text'] ),
				'comment_type'     => 'review',
				'comment_approved' => $approved,
				'comment_date'     => get_date_from_gmt( $date ),
				'comment_date_gmt' => $date,
				'comment_meta'     => $meta,
			)
		);
		++$out['imported'];
		$approved ? ++$out['approved'] : ++$out['pending'];
		if ( ! $gen && $approved ) {
			$touched[ $pid ] = true;
		}
		$gen ? ++$out['general'] : ++$out['mapped'];
	}
	// Keep WooCommerce's cached rating data in sync.
	foreach ( array_keys( $touched ) as $pid ) {
		$product = wc_get_product( $pid );
		if ( $product ) {
			$store = WC_Data_Store::load( 'product' );
			$store->update_average_rating( $product );
			$store->update_review_count( $product );
		}
	}
	return $out;
}


/**
 * Copies review photos that still sit on the old external CDN into the media library,
 * so they keep working when that service goes away. Runs in batches; call until done is true.
 */
function marcha_rest_localize_review_images( WP_REST_Request $req ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$limit  = min( 20, max( 1, (int) ( $req->get_param( 'limit' ) ?: 8 ) ) );
	$base   = wp_get_upload_dir()['baseurl'];
	$done   = 0;
	$copied = 0;
	$failed = array();
	$left   = 0;

	$comments = get_comments( array( 'type' => 'review', 'status' => 'all', 'meta_key' => 'marcha_images', 'number' => 500 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $comments as $c ) {
		$imgs = get_comment_meta( $c->comment_ID, 'marcha_images', true );
		if ( ! is_array( $imgs ) || ! $imgs ) {
			continue;
		}
		$external = array_filter( $imgs, function ( $u ) use ( $base ) {
			return 0 !== strpos( (string) $u, $base );
		} );
		if ( ! $external ) {
			continue;
		}
		if ( $done >= $limit ) {
			++$left;
			continue;
		}
		$next = array();
		foreach ( $imgs as $url ) {
			if ( 0 === strpos( (string) $url, $base ) ) {
				$next[] = $url;
				continue;
			}
			$tmp = download_url( $url, 45 );
			if ( is_wp_error( $tmp ) ) {
				$failed[] = $url;
				$next[]   = $url;
				continue;
			}
			$name = 'review-' . (int) $c->comment_ID . '-' . substr( md5( $url ), 0, 6 ) . '.jpg';
			$id   = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, 'Review photo' );
			if ( is_wp_error( $id ) ) {
				wp_delete_file( $tmp );
				$failed[] = $url;
				$next[]   = $url;
				continue;
			}
			$next[] = wp_get_attachment_url( $id );
			++$copied;
		}
		update_comment_meta( $c->comment_ID, 'marcha_images', array_values( $next ) );
		++$done;
	}
	return array( 'reviews' => $done, 'copied' => $copied, 'remaining' => $left, 'failed' => array_values( array_unique( $failed ) ), 'done' => 0 === $left );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route( 'marcha/v1', '/reviews', array(
			array( 'methods' => 'GET', 'callback' => 'marcha_rest_get_reviews', 'permission_callback' => '__return_true' ),
			array( 'methods' => 'POST', 'callback' => 'marcha_rest_submit_review', 'permission_callback' => '__return_true' ),
		) );
		register_rest_route( 'marcha/v1', '/reviews/import', array(
			'methods'             => 'POST',
			'callback'            => 'marcha_rest_import_reviews',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
		register_rest_route( 'marcha/v1', '/reviews/localize-images', array(
			'methods'             => 'POST',
			'callback'            => 'marcha_rest_localize_review_images',
			'permission_callback' => function () {
				return current_user_can( 'upload_files' );
			},
		) );
	}
);
