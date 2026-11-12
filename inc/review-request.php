<?php
/**
 * Asks a customer what she thought, once her shoes have had time to arrive and be worn.
 *
 * The shop has sold to tens of thousands of women and carries four reviews on its most
 * popular shoe, because nothing ever asked. Reviews earn the star rating Google prints
 * beside a result, and they answer the one question the shop cannot answer for a shopper
 * on its own — whether the size runs true.
 *
 * Nothing is sent until the shop owner turns this on: the option below starts at 'no', so
 * installing the theme never mails a customer by surprise.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

const MARCHA_REVIEW_OPTION = 'marcha_review_request';
const MARCHA_REVIEW_HOOK   = 'marcha_send_review_request';
const MARCHA_REVIEW_SENT   = '_marcha_review_request_sent';

/** Off until the shop owner says otherwise. */
function marcha_review_request_enabled() {
	return 'yes' === get_option( MARCHA_REVIEW_OPTION, 'no' );
}

/**
 * Days between the order being completed and the question being asked.
 *
 * Long enough for the parcel to arrive and the shoes to be worn — a woman asked the day
 * her shoes ship has nothing to say, and says nothing.
 */
function marcha_review_request_delay() {
	return (int) apply_filters( 'marcha_review_request_delay', 10 );
}

/** Products from the order that can actually be reviewed, keyed by product ID. */
function marcha_review_request_products( $order ) {
	$products = array();
	if ( ! $order instanceof WC_Order ) {
		return $products;
	}
	foreach ( $order->get_items() as $item ) {
		$id = (int) $item->get_product_id();
		if ( ! $id || isset( $products[ $id ] ) ) {
			continue;
		}
		$product = wc_get_product( $id );
		if ( $product instanceof WC_Product && 'publish' === $product->get_status() ) {
			$products[ $id ] = $product;
		}
	}

	return $products;
}

add_action(
	'woocommerce_order_status_completed',
	function ( $order_id ) {
		if ( ! marcha_review_request_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || $order->get_meta( MARCHA_REVIEW_SENT ) ) {
			return;
		}
		// Re-completing an order must not queue a second ask.
		if ( wp_next_scheduled( MARCHA_REVIEW_HOOK, array( (int) $order_id ) ) ) {
			return;
		}
		wp_schedule_single_event(
			time() + marcha_review_request_delay() * DAY_IN_SECONDS,
			MARCHA_REVIEW_HOOK,
			array( (int) $order_id )
		);
	}
);

/**
 * The email itself.
 *
 * Wrapped in WooCommerce's own header and footer so it carries the shop's branding and
 * matches every other email the customer has had from here.
 */
function marcha_review_request_body( $order ) {
	$products = marcha_review_request_products( $order );
	if ( ! $products ) {
		return '';
	}
	$first = reset( $products );
	$name  = trim( (string) $order->get_billing_first_name() );

	ob_start();
	?>
	<p style="margin:0 0 16px;font-size:16px;">
		<?php echo $name ? esc_html( sprintf( 'שלום %s,', $name ) ) : 'שלום,'; ?>
	</p>
	<p style="margin:0 0 16px;font-size:16px;line-height:1.7;">
		לפני כמה ימים הגיעו אלייך <strong><?php echo esc_html( wp_strip_all_tags( $first->get_name() ) ); ?></strong><?php echo count( $products ) > 1 ? ' ועוד זוג' : ''; ?>.
		נשמח לדעת איך הן יושבות.
	</p>
	<p style="margin:0 0 24px;font-size:16px;line-height:1.7;">
		כמה מילים שלך עוזרות לאישה הבאה לבחור מידה — וזאת השאלה היחידה שאנחנו לא יכולות לענות עליה במקומה.
	</p>
	<table cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:0 0 24px;">
		<?php foreach ( $products as $product ) : ?>
			<tr>
				<td style="padding:0 0 12px;">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) . '#reviews' ); ?>"
						style="display:inline-block;padding:12px 22px;background:#1b1917;color:#fff;text-decoration:none;font-size:15px;border-radius:2px;">
						לכתיבת ביקורת על <?php echo esc_html( wp_strip_all_tags( $product->get_name() ) ); ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<p style="margin:0 0 8px;font-size:15px;line-height:1.7;">
		ואם משהו לא התאים — המידה, הגוון, כל דבר — פשוט השיבי למייל הזה ונסדר.
	</p>
	<p style="margin:0;font-size:13px;color:#777;line-height:1.7;">
		אם אינך מעוניינת לקבל מאיתנו פניות מסוג זה, השיבי "הסר" ולא נפנה שוב.
	</p>
	<?php

	return (string) ob_get_clean();
}

/** Builds the full HTML document, header and footer included. */
function marcha_review_request_html( $order ) {
	$body = marcha_review_request_body( $order );
	if ( '' === $body || ! function_exists( 'WC' ) ) {
		return '';
	}
	$mailer  = WC()->mailer();
	$heading = 'איך הן יושבות?';

	ob_start();
	$mailer->email_header( $heading );
	echo $body; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- built above, already escaped.
	$mailer->email_footer();

	return (string) ob_get_clean();
}

add_action(
	MARCHA_REVIEW_HOOK,
	function ( $order_id ) {
		if ( ! marcha_review_request_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || $order->get_meta( MARCHA_REVIEW_SENT ) ) {
			return;
		}
		$to = $order->get_billing_email();
		if ( ! is_email( $to ) ) {
			return;
		}
		$html = marcha_review_request_html( $order );
		if ( '' === $html ) {
			return;
		}
		$sent = WC()->mailer()->send(
			$to,
			'איך הנעליים? נשמח לשמוע',
			$html,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
		// Written whatever the outcome: a mail server that refused once will refuse again,
		// and a customer must never be asked twice for the same order.
		$order->update_meta_data( MARCHA_REVIEW_SENT, $sent ? gmdate( 'c' ) : 'failed' );
		$order->save();
	}
);

/**
 * See the email for a real order without sending it.
 *
 * wp-admin/?marcha_review_preview=<order id>
 */
add_action(
	'admin_init',
	function () {
		$order_id = isset( $_GET['marcha_review_preview'] ) ? absint( $_GET['marcha_review_preview'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview behind a capability check.
		if ( ! $order_id || ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'WC' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			wp_die( 'הזמנה לא נמצאה' );
		}
		$html = marcha_review_request_html( $order );
		if ( '' === $html ) {
			wp_die( 'בהזמנה הזו אין מוצרים שאפשר לבקש עליהם ביקורת.' );
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- rendered email.
		exit;
	}
);

/** Nothing left queued if the feature is switched off mid-flight. */
add_action(
	'update_option_' . MARCHA_REVIEW_OPTION,
	function ( $old, $new ) {
		if ( 'yes' !== $new ) {
			wp_unschedule_hook( MARCHA_REVIEW_HOOK );
		}
	},
	10,
	2
);
