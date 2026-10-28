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
<div id="root"></div>
<template id="wp-native-content">
<?php
if ( marcha_is_native_route() ) {
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}
?>
</template>
<noscript>
	<main><?php while ( have_posts() ) { the_post(); the_content(); } ?></main>
</noscript>
<?php wp_footer(); ?>
</body>
</html>
