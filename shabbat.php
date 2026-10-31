<?php
/**
 * Shabbat / Yom Tov closed-shop page.
 *
 * WP Shamor decides when the shop closes and hands its own block_template.php back
 * through template_include. That page renders an [elementor-template] shortcode that
 * no longer resolves since Elementor was dropped, so functions.php swaps in this file
 * instead - the plugin keeps owning the timing and the 503 headers, we own the design.
 *
 * Included by wp-includes/template-loader.php at global scope, which is where $shamor
 * comes from.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

global $shamor;

$havdalah_hour = '';
$seconds_left  = 0;

if ( is_object( $shamor ) ) {
	if ( method_exists( $shamor, 'get_shabbat_times' ) ) {
		$times = $shamor->get_shabbat_times();
		if ( ! empty( $times['havdalah'] ) ) {
			$seconds_left = max( 0, (int) $times['havdalah'] - time() );
			if ( method_exists( $shamor, 'shamor_site_get_headers_503' ) ) {
				$shamor->shamor_site_get_headers_503( $times['havdalah'] );
			}
		}
	}
	if ( method_exists( $shamor, 'get_havdalah_hour' ) ) {
		$havdalah_hour = (string) $shamor->get_havdalah_hour();
	}
}

$fallback = 'מרשה בלרינה חוזרת לפעילות במוצאי שבת. נשמח לראותך אז.';

/* The stored notice usually opens with the same greeting as the heading. */
$lede = trim( (string) get_option( 'shamor_display_text' ) );
$lede = trim( (string) preg_replace( '/^\s*שבת\s+שלום\s*[,!.\x{05BE}-]*\s*/u', '', $lede ) );
if ( '' === $lede ) {
	$lede = $fallback;
}

$logo_url = wp_get_upload_dir()['baseurl'] . '/2025/03/Marchalogo-1-300x111.png';
?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( 'שבת שלום · ' . get_bloginfo( 'name' ) ); ?></title>
	<style>
		:root {
			--c-ink: #171717;
			--c-muted: #6b6b6b;
			--c-mint: #03ffc3;
			--c-line: rgba(23, 23, 23, .1);
			--c-bg: #fbfaf8;
		}

		*, *::before, *::after { box-sizing: border-box; }

		html, body { margin: 0; padding: 0; }

		body {
			min-height: 100svh;
			display: grid;
			place-items: center;
			padding: clamp(24px, 6vw, 64px) 16px;
			background: var(--c-bg);
			color: var(--c-ink);
			font-family: "ploni", "Heebo", "Assistant", system-ui, -apple-system, "Segoe UI", sans-serif;
			-webkit-font-smoothing: antialiased;
			text-wrap: balance;
		}

		/* Two soft mint auras instead of a flat background. */
		body::before,
		body::after {
			content: "";
			position: fixed;
			z-index: 0;
			border-radius: 50%;
			pointer-events: none;
			filter: blur(60px);
		}

		body::before {
			inset-block-start: -22vmax;
			inset-inline-end: -16vmax;
			width: 52vmax;
			aspect-ratio: 1;
			background: radial-gradient(circle, rgba(3, 255, 195, .26), transparent 68%);
		}

		body::after {
			inset-block-end: -26vmax;
			inset-inline-start: -18vmax;
			width: 46vmax;
			aspect-ratio: 1;
			background: radial-gradient(circle, rgba(100, 200, 211, .2), transparent 68%);
		}

		.card {
			position: relative;
			z-index: 1;
			width: 100%;
			max-width: 520px;
			padding: clamp(32px, 7vw, 56px) clamp(22px, 6vw, 48px);
			text-align: center;
			background: rgba(255, 255, 255, .78);
			-webkit-backdrop-filter: blur(18px) saturate(1.2);
			backdrop-filter: blur(18px) saturate(1.2);
			border: 1px solid var(--c-line);
			border-radius: 28px;
			box-shadow: 0 1px 2px rgba(23, 23, 23, .04), 0 24px 60px -24px rgba(23, 23, 23, .22);
		}

		.logo {
			display: block;
			width: 132px;
			height: auto;
			margin: 0 auto clamp(26px, 6vw, 36px);
		}

		.flame {
			display: block;
			width: 58px;
			height: auto;
			margin: 0 auto 20px;
		}

		.flame__fire { transform-origin: 50% 100%; }

		.pill {
			display: inline-block;
			margin: 0 0 16px;
			padding: 7px 16px;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: .02em;
			color: #0a6b53;
			background: rgba(3, 255, 195, .18);
			border: 1px solid rgba(3, 255, 195, .5);
			border-radius: 100px;
		}

		h1 {
			margin: 0 0 14px;
			font-size: clamp(38px, 11vw, 60px);
			font-weight: 700;
			line-height: 1.05;
			letter-spacing: -.02em;
		}

		.lede {
			margin: 0 auto;
			max-width: 34ch;
			font-size: clamp(16px, 4.2vw, 18px);
			line-height: 1.65;
			color: var(--c-muted);
		}

		.countdown {
			margin: clamp(28px, 7vw, 38px) 0 0;
			padding: clamp(18px, 5vw, 24px) 0 0;
			border-block-start: 1px solid var(--c-line);
		}

		.countdown__label {
			margin: 0 0 14px;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: .06em;
			text-transform: uppercase;
			color: var(--c-muted);
		}

		.clock {
			display: flex;
			justify-content: center;
			gap: clamp(8px, 2.6vw, 14px);
			direction: ltr;
		}

		.unit {
			flex: 0 1 84px;
			min-width: 0;
			padding: 12px 6px 10px;
			background: #fff;
			border: 1px solid var(--c-line);
			border-radius: 16px;
		}

		.unit__num {
			display: block;
			font-size: clamp(26px, 7.5vw, 34px);
			font-weight: 700;
			line-height: 1;
			font-variant-numeric: tabular-nums;
			letter-spacing: -.02em;
		}

		.unit__name {
			display: block;
			margin-top: 7px;
			font-size: 11px;
			font-weight: 600;
			letter-spacing: .04em;
			color: var(--c-muted);
		}

		.at {
			margin: 18px 0 0;
			font-size: 15px;
			color: var(--c-muted);
		}

		.at strong {
			color: var(--c-ink);
			font-variant-numeric: tabular-nums;
		}

		/* Entrance - one shared rise, staggered. */
		@media (prefers-reduced-motion: no-preference) {
			.rise {
				animation: rise .9s cubic-bezier(.22, 1, .36, 1) backwards;
			}

			.rise:nth-child(1) { animation-delay: .05s; }
			.rise:nth-child(2) { animation-delay: .12s; }
			.rise:nth-child(3) { animation-delay: .19s; }
			.rise:nth-child(4) { animation-delay: .26s; }
			.rise:nth-child(5) { animation-delay: .33s; }
			.rise:nth-child(6) { animation-delay: .4s; }

			.flame__fire { animation: flicker 2.6s ease-in-out infinite; }

			@keyframes rise {
				from { opacity: 0; transform: translateY(14px); }
				to   { opacity: 1; transform: none; }
			}

			@keyframes flicker {
				0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
				35%      { transform: scale(1.07, .96) rotate(-2.5deg); opacity: .9; }
				70%      { transform: scale(.95, 1.05) rotate(2deg); opacity: .97; }
			}
		}
	</style>
</head>
<body>
	<main class="card" role="main" data-nosnippet>
		<img class="logo rise" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="132" height="49" />

		<svg class="flame rise" viewBox="0 0 64 72" fill="none" aria-hidden="true" focusable="false">
			<g class="flame__fire">
				<path d="M32 4c6 7.5 10 13 10 18a10 10 0 0 1-20 0c0-5 4-10.5 10-18Z" fill="url(#mcFlame)" />
			</g>
			<rect x="27" y="30" width="10" height="34" rx="3" fill="#fff" stroke="rgba(23,23,23,.16)" />
			<rect x="21" y="63" width="22" height="6" rx="3" fill="rgba(23,23,23,.1)" />
			<defs>
				<linearGradient id="mcFlame" x1="32" y1="4" x2="32" y2="32" gradientUnits="userSpaceOnUse">
					<stop stop-color="#03ffc3" />
					<stop offset="1" stop-color="#64c8d3" />
				</linearGradient>
			</defs>
		</svg>

		<p class="pill rise">החנות סגורה</p>

		<h1 class="rise">שבת שלום</h1>

		<p class="lede rise"><?php echo esc_html( $lede ); ?></p>

		<?php if ( $seconds_left > 0 ) : ?>
			<section class="countdown rise" aria-labelledby="mc-cd">
				<p class="countdown__label" id="mc-cd">נפתחות מחדש בעוד</p>

				<div class="clock" id="mc-clock" aria-live="polite" aria-atomic="true">
					<div class="unit"><span class="unit__num" id="mc-h">--</span><span class="unit__name">שעות</span></div>
					<div class="unit"><span class="unit__num" id="mc-m">--</span><span class="unit__name">דקות</span></div>
					<div class="unit"><span class="unit__num" id="mc-s">--</span><span class="unit__name">שניות</span></div>
				</div>

				<?php if ( $havdalah_hour ) : ?>
					<p class="at">צאת השבת בשעה <strong><?php echo esc_html( $havdalah_hour ); ?></strong></p>
				<?php endif; ?>
			</section>

			<script>
				(function () {
					// Counted from the server's own remaining seconds, so a wrong clock
					// on the visitor's device cannot shift the time.
					var left = <?php echo (int) $seconds_left; ?>;
					var start = (window.performance && performance.now) ? performance.now() : Date.now();
					var h = document.getElementById('mc-h');
					var m = document.getElementById('mc-m');
					var s = document.getElementById('mc-s');

					function pad(n) { return n < 10 ? '0' + n : '' + n; }

					function tick() {
						var now = (window.performance && performance.now) ? performance.now() : Date.now();
						var rest = Math.max(0, left - Math.floor((now - start) / 1000));

						h.textContent = pad(Math.floor(rest / 3600));
						m.textContent = pad(Math.floor(rest % 3600 / 60));
						s.textContent = pad(rest % 60);

						if (rest <= 0) {
							// Give the server a moment to cross the line, then come back in.
							setTimeout(function () { location.reload(); }, 4000);
							return;
						}
						setTimeout(tick, 1000);
					}

					tick();
				}());
			</script>
		<?php endif; ?>
	</main>
</body>
</html>
