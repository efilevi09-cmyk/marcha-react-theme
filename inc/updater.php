<?php
/**
 * One-click theme updater: pulls the latest build from the GitHub repository.
 * Admin only (manage_options). Repository must be readable without a token.
 */
defined( 'ABSPATH' ) || exit;

const MARCHA_REPO_ZIP = 'https://codeload.github.com/efilevi09-cmyk/marcha-react-theme/zip/refs/heads/main';
const MARCHA_REPO_VER = 'https://raw.githubusercontent.com/efilevi09-cmyk/marcha-react-theme/main/style.css';

add_action( 'admin_menu', function () {
	add_theme_page( 'עדכון תבנית Marcha', 'עדכון תבנית Marcha', 'manage_options', 'marcha-update', 'marcha_update_screen' );
} );

function marcha_remote_version() {
	$res = wp_remote_get( MARCHA_REPO_VER . '?t=' . time(), array( 'timeout' => 15 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return null;
	}
	return preg_match( '/^\s*Version:\s*(.+)$/mi', wp_remote_retrieve_body( $res ), $m ) ? trim( $m[1] ) : null;
}

function marcha_run_update() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	$tmp = download_url( MARCHA_REPO_ZIP, 60 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	$dest = get_temp_dir() . 'marcha-update-' . wp_generate_password( 8, false );
	$ok   = unzip_file( $tmp, $dest );
	@unlink( $tmp );
	if ( is_wp_error( $ok ) ) {
		return $ok;
	}
	$dirs = glob( $dest . '/*', GLOB_ONLYDIR );
	if ( empty( $dirs ) ) {
		return new WP_Error( 'marcha_empty', 'הארכיון ריק' );
	}
	global $wp_filesystem;
	$res = copy_dir( $dirs[0], get_template_directory() );
	$wp_filesystem->delete( $dest, true );
	return $res;
}

function marcha_update_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['marcha_update'] ) && check_admin_referer( 'marcha_update' ) ) {
		$r   = marcha_run_update();
		$msg = is_wp_error( $r ) ? '<div class="notice notice-error"><p>' . esc_html( $r->get_error_message() ) . '</p></div>' : '<div class="notice notice-success"><p>התבנית עודכנה.</p></div>';
	}
	$local  = wp_get_theme( 'marcha-react-theme' )->get( 'Version' );
	$remote = marcha_remote_version();
	echo '<div class="wrap"><h1>עדכון תבנית Marcha</h1>' . $msg; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<p>גרסה מותקנת: <b>' . esc_html( $local ) . '</b> · גרסה במאגר: <b>' . esc_html( $remote ?: 'לא זמין' ) . '</b></p>';
	echo '<form method="post">';
	wp_nonce_field( 'marcha_update' );
	echo '<button class="button button-primary" name="marcha_update" value="1">עדכני עכשיו</button></form></div>';
}
