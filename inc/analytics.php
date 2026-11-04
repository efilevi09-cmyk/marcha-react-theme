<?php
/**
 * Third-party analytics that the theme owns.
 *
 * Microsoft Clarity (session recordings and heatmaps) used to arrive through the old
 * page builder and went missing with it, so the Clarity project sat empty. It is
 * printed here instead, which keeps it alive across theme updates.
 *
 * GA4 and the Meta pixel still come from Site Kit and PixelYourSite; the SPA's own
 * ecommerce events are in src/lib/track.js.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

/** Clarity project for marchaballerina.com. */
const MARCHA_CLARITY_ID = 'xad3l94vka';

add_action(
	'wp_head',
	function () {
		if ( is_admin() || ! MARCHA_CLARITY_ID ) {
			return;
		}

		/**
		 * Skip the shop owner's own browsing.
		 *
		 * Off by default: she is in the shop all day, and her sessions would crowd out
		 * the customers'. Flip this to true to keep staff out of the recordings.
		 */
		if ( apply_filters( 'marcha_clarity_skip_staff', false ) && current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script",<?php echo wp_json_encode( MARCHA_CLARITY_ID ); ?>);</script>
		<?php
	},
	5
);
