<?php
/**
 * Order emails.
 *
 * The notification that lands in the shop owner's inbox opens with the order table,
 * where the amount actually charged is the last line and a bundle discount shows up
 * as just another row. This puts the paid total, the discount and the payment method
 * at the very top, so a glance at the phone answers "how much came in".
 *
 * Admin notifications only - customers get WooCommerce's own layout untouched.
 *
 * @package Marcha
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every negative fee on the order, as label => formatted amount.
 *
 * @param WC_Order $order Order.
 * @return array<string,string>
 */
function marcha_order_discount_rows( WC_Order $order ) {
	$rows = array();

	foreach ( $order->get_fees() as $fee ) {
		$total = (float) $fee->get_total();
		if ( $total >= 0 ) {
			continue;
		}
		// Absolute value on purpose: a leading minus flips to the wrong side of the
		// number in an RTL email, so the saving is stated in words instead.
		$rows[ $fee->get_name() ] = wp_strip_all_tags(
			wc_price( abs( $total ), array( 'currency' => $order->get_currency() ) )
		);
	}

	return $rows;
}

add_action(
	'woocommerce_email_before_order_table',
	/**
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Admin notification?
	 * @param bool     $plain_text    Plain-text email?
	 */
	function ( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! $sent_to_admin || ! $order instanceof WC_Order ) {
			return;
		}

		$paid      = $order->is_paid();
		$total     = wp_strip_all_tags( $order->get_formatted_order_total() );
		$discounts = marcha_order_discount_rows( $order );
		$method    = $order->get_payment_method_title();
		$count     = (int) $order->get_item_count();

		if ( $plain_text ) {
			echo "\n", esc_html( $paid ? 'שולם' : 'סה"כ להזמנה' ), ': ', esc_html( $total ), "\n";
			foreach ( $discounts as $label => $amount ) {
				echo esc_html( $label ), ' - נחסכו ', esc_html( $amount ), "\n";
			}
			echo esc_html( sprintf( '%d פריטים', $count ) );
			if ( $method ) {
				echo ' · ', esc_html( $method );
			}
			echo "\n\n----------\n\n";
			return;
		}

		$meta = array( sprintf( _n( 'פריט אחד', '%d פריטים', $count, 'marcha' ), $count ) );
		if ( $method ) {
			$meta[] = $method;
		}
		if ( ! $paid ) {
			$meta[] = 'טרם שולם';
		}
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 28px;border-collapse:separate;">
			<tr>
				<td style="padding:22px 24px;background:#fbfaf8;border:1px solid #e5e5e5;border-radius:10px;direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;">

					<p style="margin:0 0 4px;font-size:13px;line-height:1.4;color:#6b6b6b;letter-spacing:.02em;">
						<?php echo esc_html( $paid ? 'סכום ששולם' : 'סה"כ להזמנה' ); ?>
					</p>

					<p style="margin:0;font-size:32px;line-height:1.15;font-weight:bold;color:#171717;">
						<?php echo esc_html( $total ); ?>
					</p>

					<?php foreach ( $discounts as $label => $amount ) : ?>
						<p style="margin:12px 0 0;">
							<span style="display:inline-block;padding:6px 14px;background:#e6fff8;border:1px solid #9df3dd;border-radius:100px;font-size:13px;line-height:1.4;color:#0a6b53;">
								<?php echo esc_html( $label ); ?> · נחסכו <?php echo esc_html( $amount ); ?>
							</span>
						</p>
					<?php endforeach; ?>

					<p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:#6b6b6b;">
						<?php echo esc_html( implode( ' · ', $meta ) ); ?>
					</p>

				</td>
			</tr>
		</table>
		<?php
	},
	5,
	3
);
