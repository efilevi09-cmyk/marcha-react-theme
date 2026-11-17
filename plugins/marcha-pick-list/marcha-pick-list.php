<?php
/**
 * Plugin Name:       מרשה בלרינה – ליקוט ותעודות משלוח
 * Plugin URI:        https://marchaballerina.com/
 * Description:       מוסיפה למסך ההזמנות רשימת ליקוט מאוחדת, תעודת משלוח ומדבקת כתובת למשלוח — כפעולות קבוצתיות וככפתורים בכל שורה. הכל בעברית תקינה: HTML שמודפס מהדפדפן, בלי מנוע PDF שלא תומך ב-RTL.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Marcha Ballerina
 * Text Domain:       marcha-pick-list
 *
 * @package MarchaPickList
 */

namespace Marcha\PickList;

defined( 'ABSPATH' ) || exit;

const VERSION      = '1.2.0';
const ACTION_PICK  = 'marcha_pick_list';
const ACTION_SLIP  = 'marcha_packing_slip';
const ACTION_LABEL = 'marcha_address_label';
const NONCE        = 'marcha_docs_render';
const QUERY_FLAG   = 'marcha_doc';
const CAPABILITY   = 'manage_woocommerce';

/* =========================================================================
 * Registration
 * ====================================================================== */

/**
 * The orders screen lives at a different hook depending on whether the store
 * runs the legacy post table or HPOS, so both are registered.
 */
function register_hooks() {
	$screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );

	foreach ( $screens as $screen ) {
		add_filter( "bulk_actions-{$screen}", __NAMESPACE__ . '\\add_bulk_actions' );
		add_filter( "handle_bulk_actions-{$screen}", __NAMESPACE__ . '\\handle_bulk_action', 10, 3 );
	}

	add_action( 'admin_init', __NAMESPACE__ . '\\maybe_render', 5 );
	add_action( 'woocommerce_admin_order_actions_end', __NAMESPACE__ . '\\row_action', 10, 1 );
}

if ( is_admin() ) {
	register_hooks();
}

/**
 * Keeps the plugin listed as compatible when the store moves to HPOS.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * @param array<string,string> $actions
 * @return array<string,string>
 */
function add_bulk_actions( $actions ) {
	$actions = is_array( $actions ) ? $actions : array();

	if ( current_user_can( CAPABILITY ) ) {
		$actions[ ACTION_PICK ] = __( 'רשימת ליקוט למחסן', 'marcha-pick-list' );
		$actions[ ACTION_SLIP ]  = __( 'תעודת משלוח להדפסה', 'marcha-pick-list' );
		$actions[ ACTION_LABEL ] = __( 'מדבקת משלוח להדפסה', 'marcha-pick-list' );
	}

	return $actions;
}

/**
 * Builds the redirect that renders the document. Nothing is written to the
 * database — the bulk action only reads the selected orders.
 *
 * @param string $redirect_to
 * @param string $action
 * @param int[]  $order_ids
 * @return string
 */
function handle_bulk_action( $redirect_to, $action, $order_ids ) {
	if ( ! in_array( $action, array( ACTION_PICK, ACTION_SLIP, ACTION_LABEL ), true ) || ! current_user_can( CAPABILITY ) ) {
		return $redirect_to;
	}

	$order_ids = is_array( $order_ids ) ? array_values( array_filter( array_map( 'absint', $order_ids ) ) ) : array();

	if ( empty( $order_ids ) ) {
		return $redirect_to;
	}

	$map = array(
		ACTION_PICK  => 'pick',
		ACTION_SLIP  => 'slip',
		ACTION_LABEL => 'label',
	);

	return document_url( $map[ $action ], $order_ids );
}

/**
 * Single-order shortcuts on each row of the orders table.
 *
 * @param \WC_Order $order
 */
function row_action( $order ) {
	if ( ! $order instanceof \WC_Order || ! current_user_can( CAPABILITY ) ) {
		return;
	}

	$buttons = array(
		array(
			'doc'   => 'slip',
			'label' => __( 'ת. משלוח', 'marcha-pick-list' ),
			'tip'   => __( 'תעודת משלוח', 'marcha-pick-list' ),
			'style' => '',
		),
		array(
			'doc'   => 'label',
			'label' => __( 'מדבקה', 'marcha-pick-list' ),
			'tip'   => __( 'מדבקת כתובת למשלוח', 'marcha-pick-list' ),
			'style' => 'background:#ea580c;border-color:#c2410c;color:#fff;text-shadow:none;box-shadow:0 1px 0 #9a3412;',
		),
	);

	foreach ( $buttons as $button ) {
		printf(
			'<a class="button marcha-doc-btn marcha-doc-%1$s" href="%2$s" target="_blank" rel="noopener" title="%3$s" aria-label="%3$s" style="%4$s">%5$s</a> ',
			esc_attr( $button['doc'] ),
			esc_url( document_url( $button['doc'], array( $order->get_id() ) ) ),
			esc_attr( $button['tip'] ),
			esc_attr( $button['style'] ),
			esc_html( $button['label'] )
		);
	}
}

/**
 * @param string $doc
 * @param int[]  $order_ids
 * @return string
 */
function document_url( $doc, $order_ids ) {
	$url = add_query_arg(
		array(
			QUERY_FLAG => $doc,
			'ids'      => implode( ',', array_map( 'absint', $order_ids ) ),
		),
		admin_url( 'admin.php' )
	);

	return wp_nonce_url( $url, NONCE );
}

/* =========================================================================
 * Dispatch
 * ====================================================================== */

function maybe_render() {
	if ( empty( $_GET[ QUERY_FLAG ] ) ) {
		return;
	}

	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'אין לך הרשאה להפיק את המסמך הזה.', 'marcha-pick-list' ), 403 );
	}

	check_admin_referer( NONCE );

	$doc       = sanitize_key( wp_unslash( $_GET[ QUERY_FLAG ] ) );
	$raw       = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
	$order_ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );

	if ( empty( $order_ids ) ) {
		wp_die( esc_html__( 'לא נבחרו הזמנות.', 'marcha-pick-list' ) );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );

	if ( 'label' === $doc ) {
		render_labels( $order_ids );
	} elseif ( 'slip' === $doc ) {
		render_packing_slips( $order_ids );
	} else {
		render_pick_list( collect_lines( $order_ids ), $order_ids );
	}

	exit;
}

/* =========================================================================
 * Data
 * ====================================================================== */

/**
 * Aggregates every line item of the selected orders into one row per
 * product + variation, so the same shoe in two sizes stays two rows.
 *
 * @param int[] $order_ids
 * @return array<string,array<string,mixed>>
 */
function collect_lines( $order_ids ) {
	$lines = array();

	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			continue;
		}

		$order_label = $order->get_order_number();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product   = $item->get_product();
			$variation = ( $product && $product->is_type( 'variation' ) ) ? $product : null;
			$parent_id = $variation ? $variation->get_parent_id() : ( $product ? $product->get_id() : 0 );
			$parent    = $parent_id ? wc_get_product( $parent_id ) : null;
			$key       = $parent_id . ':' . ( $variation ? $variation->get_id() : 0 );
			$quantity  = (int) $item->get_quantity();

			if ( isset( $lines[ $key ] ) ) {
				$lines[ $key ]['qty']     += $quantity;
				$lines[ $key ]['orders'][] = $order_label;
				continue;
			}

			$lines[ $key ] = array(
				'name'     => $parent ? $parent->get_name() : $item->get_name(),
				'size'     => line_size( $item, $variation ),
				'sku'      => $product ? (string) $product->get_sku() : '',
				'qty'      => $quantity,
				'orders'   => array( $order_label ),
				'image'    => line_image( $variation, $parent ),
				'category' => line_category( $parent_id ),
			);
		}
	}

	uasort( $lines, __NAMESPACE__ . '\\compare_lines' );

	return $lines;
}

/**
 * @param array<string,mixed> $a
 * @param array<string,mixed> $b
 * @return int
 */
function compare_lines( $a, $b ) {
	$by_category = strnatcasecmp( (string) $a['category'], (string) $b['category'] );

	if ( 0 !== $by_category ) {
		return $by_category;
	}

	$by_name = strnatcasecmp( (string) $a['name'], (string) $b['name'] );

	return 0 !== $by_name ? $by_name : strnatcasecmp( (string) $a['size'], (string) $b['size'] );
}

/**
 * The size is what the picker actually walks the shelf with, so the variation
 * attributes come first and the item meta is the fallback.
 *
 * @param \WC_Order_Item_Product $item
 * @param \WC_Product|null       $variation
 * @return string
 */
function line_size( $item, $variation ) {
	if ( $variation ) {
		$values = array();

		foreach ( $variation->get_variation_attributes() as $value ) {
			$value = trim( (string) $value );

			if ( '' !== $value ) {
				$values[] = $value;
			}
		}

		if ( ! empty( $values ) ) {
			return implode( ' · ', $values );
		}
	}

	$values = array();

	foreach ( $item->get_formatted_meta_data( '' ) as $meta ) {
		$values[] = wp_strip_all_tags( (string) $meta->display_value );
	}

	return trim( implode( ' · ', array_filter( array_map( 'trim', $values ) ) ) );
}

/**
 * @param \WC_Product|null $variation
 * @param \WC_Product|null $parent
 * @return string
 */
function line_image( $variation, $parent ) {
	foreach ( array( $variation, $parent ) as $candidate ) {
		if ( ! $candidate ) {
			continue;
		}

		$id = (int) $candidate->get_image_id();

		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'woocommerce_thumbnail' );

			if ( $src ) {
				return $src;
			}
		}
	}

	return '';
}

/**
 * @param int $parent_id
 * @return string
 */
function line_category( $parent_id ) {
	if ( ! $parent_id ) {
		return __( 'ללא קטגוריה', 'marcha-pick-list' );
	}

	$terms = get_the_terms( $parent_id, 'product_cat' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return __( 'ללא קטגוריה', 'marcha-pick-list' );
	}

	$names = wp_list_pluck( $terms, 'name' );
	sort( $names, SORT_NATURAL | SORT_FLAG_CASE );

	return (string) reset( $names );
}

/* =========================================================================
 * Shared chrome
 * ====================================================================== */

/**
 * @param string $title
 * @param string $page_rule CSS @page rule for this document.
 */
function print_head( $title, $page_rule = 'size:A4;margin:12mm', $page_class = '' ) {
	?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $title ); ?></title>
<style>
:root{
	--ink:#111418;
	--muted:#6b7280;
	--line:#e5e7eb;
	--accent:#0f766e;
	--radius:10px;
	--pad:clamp(16px,3vw,32px);
}
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{
	margin:0;padding:var(--pad);background:#f6f7f8;color:var(--ink);
	font-family:"Ploni","Assistant","Rubik",-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
	font-size:15px;line-height:1.5;
}
.sheet{max-width:980px;margin-inline:auto;background:#fff;border-radius:var(--radius);padding:var(--pad);box-shadow:0 1px 3px rgba(0,0,0,.08)}
.sheet + .sheet{margin-top:24px}
h1{margin:0;font-size:clamp(20px,3vw,26px);letter-spacing:-.01em}
.toolbar{max-width:980px;margin:0 auto 18px;display:flex;gap:10px}
.toolbar button{font:inherit;font-weight:600;cursor:pointer;border:1px solid var(--line);background:#fff;color:var(--ink);padding:9px 18px;border-radius:8px;transition:background .15s ease,border-color .15s ease}
.toolbar button:hover{background:#f3f4f6;border-color:#d1d5db}
.toolbar button.primary{background:var(--accent);border-color:var(--accent);color:#fff}
.toolbar button.primary:hover{background:#115e56}
table{width:100%;border-collapse:collapse}
th,td{text-align:right;padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:middle}
thead th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);border-bottom:1px solid var(--ink);padding-block:6px}
td.thumb{width:62px}
td.thumb img,td.thumb .placeholder{display:block;width:54px;height:54px;object-fit:cover;border-radius:6px;background:#f3f4f6}
td.name{font-weight:600;line-height:1.35}
td.size{white-space:nowrap;font-variant-numeric:tabular-nums;font-weight:700;font-size:17px}
td.sku{color:var(--muted);font-size:13px;white-space:nowrap}
td.qty{font-size:19px;font-weight:700;text-align:center;width:66px;font-variant-numeric:tabular-nums}
td.check{width:40px;text-align:center}
td.check span{display:inline-block;width:20px;height:20px;border:2px solid #9ca3af;border-radius:4px}
.empty{padding:40px 0;text-align:center;color:var(--muted)}
@media print{
	body{background:#fff;padding:0;font-size:12.5px}
	.sheet{box-shadow:none;border-radius:0;max-width:none;padding:0}
	.sheet + .sheet{margin-top:0;break-before:page}
	.toolbar{display:none}
	thead{display:table-header-group}
	tr{break-inside:avoid}
	td.thumb img,td.thumb .placeholder{width:40px;height:40px}
	@page{<?php echo esc_html( $page_rule ); ?>}
}
/* pick list */
header.masthead{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:baseline;justify-content:space-between;border-bottom:2px solid var(--ink);padding-bottom:14px;margin-bottom:22px}
.meta{color:var(--muted);font-size:14px;display:flex;gap:18px;flex-wrap:wrap}
.meta b{color:var(--ink);font-weight:700}
h2.group{font-size:15px;margin:26px 0 8px;padding:7px 12px;background:#f3f4f6;border-radius:6px}
h2.group:first-of-type{margin-top:0}
footer.orders-included{margin-top:28px;padding-top:14px;border-top:1px solid var(--line);color:var(--muted);font-size:13px}
@media print{h2.group{background:transparent;border-bottom:1.5px solid var(--ink);border-radius:0;padding-inline:0;break-after:avoid}}
/* packing slip */
.slip-head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;border-bottom:2px solid var(--ink);padding-bottom:16px;margin-bottom:20px}
.slip-head .brand{font-size:20px;font-weight:700;letter-spacing:-.01em}
.slip-head .brand small{display:block;font-size:13px;font-weight:400;color:var(--muted);margin-top:2px}
.slip-head .doc{text-align:left}
.slip-head .doc .num{font-size:24px;font-weight:700;font-variant-numeric:tabular-nums}
.slip-head .doc .label{font-size:13px;color:var(--muted)}
.panels{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px;margin-bottom:22px}
.panel h3{margin:0 0 6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);font-weight:700}
.panel .body{font-size:14px;line-height:1.6}
.panel .body strong{font-size:15px}
.note{margin-top:18px;padding:12px 14px;border-inline-start:3px solid var(--accent);background:#f0fdfa;border-radius:0 6px 6px 0;font-size:14px}
.slip-foot{margin-top:26px;padding-top:12px;border-top:1px solid var(--line);color:var(--muted);font-size:12.5px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}

/* address label */
body.labels{background:#f6f7f8;padding:24px}
.label-sheet{width:100mm;min-height:150mm;margin:0 auto 20px;background:#fff;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.08);padding:10mm;display:flex;flex-direction:column;gap:6mm}
.label-sheet .to{flex:1}
.label-sheet .eyebrow{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);font-weight:700;margin-bottom:3mm}
.label-sheet .to-name{font-size:25px;font-weight:700;line-height:1.2;margin-bottom:2mm}
.label-sheet .to-address{font-size:19px;line-height:1.45}
.label-sheet .to-phone{margin-top:3mm;font-size:21px;font-weight:700;font-variant-numeric:tabular-nums}
.label-sheet .order{border-top:2px solid var(--ink);border-bottom:2px solid var(--ink);padding:3mm 0;display:flex;justify-content:space-between;align-items:baseline;gap:8px}
.label-sheet .order .num{font-size:27px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.01em}
.label-sheet .order .units{font-size:15px;color:var(--muted)}
.label-sheet .from{font-size:13px;line-height:1.5;color:var(--muted)}
.label-sheet .from b{color:var(--ink);font-size:14px;display:block}
@media print{
	body.labels{background:#fff;padding:0}
	.label-sheet{width:auto;min-height:0;margin:0;border-radius:0;box-shadow:none;padding:0;height:100%}
	.label-sheet + .label-sheet{break-before:page}
}
</style>
</head>
<body<?php echo 'labels' === $page_class ? ' class="labels"' : ''; ?>>
	<?php
}

function print_toolbar() {
	?>
	<div class="toolbar">
		<button type="button" class="primary" onclick="window.print()"><?php esc_html_e( 'הדפסה', 'marcha-pick-list' ); ?></button>
		<button type="button" onclick="window.close()"><?php esc_html_e( 'סגירה', 'marcha-pick-list' ); ?></button>
	</div>
	<?php
}

function print_foot() {
	echo "</body>\n</html>";
}

/* =========================================================================
 * Pick list
 * ====================================================================== */

/**
 * @param array<string,array<string,mixed>> $lines
 * @param int[]                             $order_ids
 */
function render_pick_list( $lines, $order_ids ) {
	$total_units = array_sum( wp_list_pluck( $lines, 'qty' ) );
	$printed_at  = wp_date( 'd/m/Y H:i' );
	$grouped     = array();

	foreach ( $lines as $line ) {
		$grouped[ $line['category'] ][] = $line;
	}

	print_head( __( 'רשימת ליקוט', 'marcha-pick-list' ) . ' · ' . $printed_at );
	print_toolbar();
	?>
<main class="sheet">
	<header class="masthead">
		<h1><?php esc_html_e( 'רשימת ליקוט', 'marcha-pick-list' ); ?></h1>
		<div class="meta">
			<span><?php esc_html_e( 'הזמנות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) count( $order_ids ) ); ?></b></span>
			<span><?php esc_html_e( 'שורות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) count( $lines ) ); ?></b></span>
			<span><?php esc_html_e( 'סה״כ זוגות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) $total_units ); ?></b></span>
			<span><?php echo esc_html( $printed_at ); ?></span>
		</div>
	</header>

	<?php if ( empty( $lines ) ) : ?>
		<p class="empty"><?php esc_html_e( 'אין פריטים בהזמנות שנבחרו.', 'marcha-pick-list' ); ?></p>
	<?php else : ?>
		<?php foreach ( $grouped as $category => $rows ) : ?>
			<h2 class="group"><?php echo esc_html( $category ); ?> <span style="color:var(--muted);font-weight:400">· <?php echo esc_html( (string) array_sum( wp_list_pluck( $rows, 'qty' ) ) ); ?></span></h2>
			<table>
				<thead>
					<tr>
						<th class="check"></th>
						<th colspan="2"><?php esc_html_e( 'מוצר', 'marcha-pick-list' ); ?></th>
						<th><?php esc_html_e( 'מידה', 'marcha-pick-list' ); ?></th>
						<th><?php esc_html_e( 'מק״ט', 'marcha-pick-list' ); ?></th>
						<th style="text-align:center"><?php esc_html_e( 'כמות', 'marcha-pick-list' ); ?></th>
						<th><?php esc_html_e( 'הזמנות', 'marcha-pick-list' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td class="check"><span></span></td>
						<td class="thumb">
							<?php if ( $row['image'] ) : ?>
								<img src="<?php echo esc_url( $row['image'] ); ?>" alt="" width="54" height="54">
							<?php else : ?>
								<span class="placeholder"></span>
							<?php endif; ?>
						</td>
						<td class="name"><?php echo esc_html( $row['name'] ); ?></td>
						<td class="size"><?php echo esc_html( $row['size'] ? $row['size'] : '—' ); ?></td>
						<td class="sku"><?php echo esc_html( $row['sku'] ? $row['sku'] : '—' ); ?></td>
						<td class="qty"><?php echo esc_html( (string) $row['qty'] ); ?></td>
						<td style="color:var(--muted);font-size:12px"><?php echo esc_html( implode( ', ', array_unique( $row['orders'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	<?php endif; ?>

	<footer class="orders-included">
		<?php esc_html_e( 'הזמנות שנכללו:', 'marcha-pick-list' ); ?>
		<?php
		$labels = array();

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$labels[] = '#' . $order->get_order_number();
			}
		}

		echo esc_html( implode( ' · ', $labels ) );
		?>
	</footer>
</main>
	<?php
	print_foot();
}

/* =========================================================================
 * Packing slips
 * ====================================================================== */

/**
 * @param int[] $order_ids
 */
function render_packing_slips( $order_ids ) {
	print_head( __( 'תעודת משלוח', 'marcha-pick-list' ) );
	print_toolbar();

	$store_name = get_bloginfo( 'name' );
	$store_line = store_address_line();
	$printed    = 0;

	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			continue;
		}

		++$printed;
		render_packing_slip( $order, $store_name, $store_line );
	}

	if ( 0 === $printed ) {
		echo '<main class="sheet"><p class="empty">' . esc_html__( 'לא נמצאו הזמנות.', 'marcha-pick-list' ) . '</p></main>';
	}

	print_foot();
}

/**
 * @param \WC_Order $order
 * @param string    $store_name
 * @param string    $store_line
 */
function render_packing_slip( $order, $store_name, $store_line ) {
	$shipping = $order->get_formatted_shipping_address();
	$billing  = $order->get_formatted_billing_address();
	$address  = $shipping ? $shipping : $billing;
	$name     = trim( $order->get_formatted_shipping_full_name() );

	if ( '' === $name ) {
		$name = trim( $order->get_formatted_billing_full_name() );
	}

	$phone = $order->get_billing_phone();
	$email = $order->get_billing_email();
	$note  = $order->get_customer_note();
	$items = $order->get_items();
	$units = 0;

	foreach ( $items as $item ) {
		$units += (int) $item->get_quantity();
	}
	?>
<main class="sheet">
	<div class="slip-head">
		<div class="brand">
			<?php echo esc_html( $store_name ); ?>
			<?php if ( $store_line ) : ?>
				<small><?php echo esc_html( $store_line ); ?></small>
			<?php endif; ?>
		</div>
		<div class="doc">
			<div class="label"><?php esc_html_e( 'תעודת משלוח', 'marcha-pick-list' ); ?></div>
			<div class="num">#<?php echo esc_html( $order->get_order_number() ); ?></div>
			<div class="label"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ); ?></div>
		</div>
	</div>

	<div class="panels">
		<div class="panel">
			<h3><?php esc_html_e( 'נשלח אל', 'marcha-pick-list' ); ?></h3>
			<div class="body">
				<strong><?php echo esc_html( $name ); ?></strong><br>
				<?php echo wp_kses_post( $address ? $address : '&mdash;' ); ?>
			</div>
		</div>
		<div class="panel">
			<h3><?php esc_html_e( 'יצירת קשר', 'marcha-pick-list' ); ?></h3>
			<div class="body">
				<?php if ( $phone ) : ?>
					<span dir="ltr"><?php echo esc_html( $phone ); ?></span><br>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<span dir="ltr"><?php echo esc_html( $email ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<div class="panel">
			<h3><?php esc_html_e( 'משלוח', 'marcha-pick-list' ); ?></h3>
			<div class="body">
				<?php echo esc_html( $order->get_shipping_method() ? $order->get_shipping_method() : '—' ); ?><br>
				<?php
				printf(
					/* translators: %d: number of units */
					esc_html__( '%d פריטים', 'marcha-pick-list' ),
					(int) $units
				);
				?>
			</div>
		</div>
	</div>

	<table>
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'מוצר', 'marcha-pick-list' ); ?></th>
				<th><?php esc_html_e( 'מידה', 'marcha-pick-list' ); ?></th>
				<th><?php esc_html_e( 'מק״ט', 'marcha-pick-list' ); ?></th>
				<th style="text-align:center"><?php esc_html_e( 'כמות', 'marcha-pick-list' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product   = $item->get_product();
			$variation = ( $product && $product->is_type( 'variation' ) ) ? $product : null;
			$parent_id = $variation ? $variation->get_parent_id() : ( $product ? $product->get_id() : 0 );
			$parent    = $parent_id ? wc_get_product( $parent_id ) : null;
			$image     = line_image( $variation, $parent );
			?>
			<tr>
				<td class="thumb">
					<?php if ( $image ) : ?>
						<img src="<?php echo esc_url( $image ); ?>" alt="" width="54" height="54">
					<?php else : ?>
						<span class="placeholder"></span>
					<?php endif; ?>
				</td>
				<td class="name"><?php echo esc_html( $parent ? $parent->get_name() : $item->get_name() ); ?></td>
				<td class="size"><?php echo esc_html( line_size( $item, $variation ) ?: '—' ); ?></td>
				<td class="sku"><?php echo esc_html( $product && $product->get_sku() ? $product->get_sku() : '—' ); ?></td>
				<td class="qty"><?php echo esc_html( (string) (int) $item->get_quantity() ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>

	<?php if ( $note ) : ?>
		<div class="note">
			<strong><?php esc_html_e( 'הערת לקוחה:', 'marcha-pick-list' ); ?></strong>
			<?php echo esc_html( $note ); ?>
		</div>
	<?php endif; ?>

	<div class="slip-foot">
		<span><?php esc_html_e( 'תודה שקנית אצלנו 🤍 החלפה והחזרה עד 14 יום.', 'marcha-pick-list' ); ?></span>
		<span dir="ltr"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
	</div>
</main>
	<?php
}


/* =========================================================================
 * Address labels
 * ====================================================================== */

/**
 * One 100x150mm shipping label per order, the size a thermal label printer
 * and a standard A6 sticker both take.
 *
 * @param int[] $order_ids
 */
function render_labels( $order_ids ) {
	print_head( __( 'מדבקות משלוח', 'marcha-pick-list' ), 'size:100mm 150mm;margin:6mm', 'labels' );
	print_toolbar();

	$store_name = get_bloginfo( 'name' );
	$store_line = store_address_line();
	$printed    = 0;

	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			continue;
		}

		++$printed;
		render_label( $order, $store_name, $store_line );
	}

	if ( 0 === $printed ) {
		echo '<div class="label-sheet"><p class="empty">' . esc_html__( 'לא נמצאו הזמנות.', 'marcha-pick-list' ) . '</p></div>';
	}

	print_foot();
}

/**
 * @param \WC_Order $order
 * @param string    $store_name
 * @param string    $store_line
 */
function render_label( $order, $store_name, $store_line ) {
	$address = $order->get_formatted_shipping_address();

	if ( ! $address ) {
		$address = $order->get_formatted_billing_address();
	}

	$name = trim( $order->get_formatted_shipping_full_name() );

	if ( '' === $name ) {
		$name = trim( $order->get_formatted_billing_full_name() );
	}

	$phone = method_exists( $order, 'get_shipping_phone' ) ? $order->get_shipping_phone() : '';

	if ( ! $phone ) {
		$phone = $order->get_billing_phone();
	}

	$units = 0;

	foreach ( $order->get_items() as $item ) {
		$units += (int) $item->get_quantity();
	}

	// The name already heads the label, so it is dropped from the address block.
	$lines = array_filter( array_map( 'trim', explode( '<br/>', (string) $address ) ) );

	if ( ! empty( $lines ) && $lines[ array_key_first( $lines ) ] === $name ) {
		array_shift( $lines );
	}
	?>
<section class="label-sheet">
	<div class="to">
		<div class="eyebrow"><?php esc_html_e( 'אל', 'marcha-pick-list' ); ?></div>
		<div class="to-name"><?php echo esc_html( $name ); ?></div>
		<div class="to-address"><?php echo wp_kses_post( implode( '<br>', $lines ) ); ?></div>
		<?php if ( $phone ) : ?>
			<div class="to-phone" dir="ltr"><?php echo esc_html( $phone ); ?></div>
		<?php endif; ?>
	</div>

	<div class="order">
		<span class="num">#<?php echo esc_html( $order->get_order_number() ); ?></span>
		<span class="units">
			<?php
			printf(
				/* translators: %d: number of units */
				esc_html__( '%d פריטים', 'marcha-pick-list' ),
				(int) $units
			);
			?>
			· <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ); ?>
		</span>
	</div>

	<div class="from">
		<?php esc_html_e( 'מאת', 'marcha-pick-list' ); ?>
		<b><?php echo esc_html( $store_name ); ?></b>
		<?php echo esc_html( $store_line ); ?>
		<span dir="ltr"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
	</div>
</section>
	<?php
}

/**
 * @return string
 */
function store_address_line() {
	$parts = array(
		get_option( 'woocommerce_store_address' ),
		get_option( 'woocommerce_store_address_2' ),
		get_option( 'woocommerce_store_city' ),
	);

	$parts = array_filter( array_map( 'trim', array_map( 'strval', $parts ) ) );

	return implode( ', ', $parts );
}
