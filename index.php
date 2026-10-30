<?php
/**
 * Single entry template. React renders header, footer and SPA routes.
 * WordPress-rendered content (checkout, my-account, generic pages) is passed in a <template>.
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'marcha-app' ); ?>>
<?php wp_body_open(); ?>
<div id="root"><?php echo marcha_seo_fallback_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
<div id="wp-native-content" class="native-content__source">
<?php
if ( marcha_is_native_route() ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}
?>
</div>
<?php // Without JavaScript the server-rendered content above is simply unhidden.
      // It must never be printed a second time inside <noscript>: WooCommerce's own
      // <noscript> button closes ours early, and the copy spills out below the footer. ?>
<noscript><style>.native-content__source{position:static!important;width:auto!important;height:auto!important;overflow:visible!important;clip-path:none!important;pointer-events:auto!important}</style></noscript>
<?php wp_footer(); ?>
</body>
</html>
