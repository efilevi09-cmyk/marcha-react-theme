<?php
/**
 * Plugin Name:       מרשה בלרינה – רשימת ליקוט
 * Plugin URI:        https://marchaballerina.com/
 * Description:       מוסיפה פעולה קבוצתית במסך ההזמנות שמפיקה רשימת ליקוט מאוחדת לכמה הזמנות יחד — מקובצת לפי קטגוריה, עם תמונת מוצר, מידה, מק״ט וכמות. מיועדת להדפסה ישירה.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Marcha Ballerina
 * Text Domain:       marcha-pick-list
 * Domain Path:       /languages
 *
 * @package MarchaPickList
 */

namespace Marcha\PickList;

defined( 'ABSPATH' ) || exit;

const VERSION    = '1.0.0';
const ACTION     = 'marcha_pick_list';
const NONCE      = 'marcha_pick_list_render';
const QUERY_FLAG = 'marcha_pick_list';
const CAPABILITY = 'manage_woocommerce';

/* -------------------------------------------------------------------------
 * Bulk action registration
 * ---------------------------------------------------------------------- */

/**
 * The orders screen lives at a different hook depending on whether the store
 * runs the legacy post table or HPOS, so both are registered.
 */
function register_hooks() {
	$screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );

	foreach ( $screens as $screen ) {
		add_filter( "bulk_actions-{$screen}", __NAMESPACE__ . '\\add_bulk_action' );
		add_filter( "handle_bulk_actions-{$screen}", __NAMESPACE__ . '\\handle_bulk_action', 10, 3 );
	}

	add_action( 'admin_init', __NAMESPACE__ . '\\maybe_render', 5 );
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
function add_bulk_action( $actions ) {
	$actions = is_array( $actions ) ? $actions : array();

	if ( current_user_can( CAPABILITY ) ) {
		$actions[ ACTION ] = __( 'רשימת ליקוט למחסן', 'marcha-pick-list' );
	}

	return $actions;
}

/**
 * Builds the redirect that renders the list. Nothing is written to the
 * database — the bulk action only reads the selected orders.
 *
 * @param string    $redirect_to
 * @param string    $action
 * @param int[]     $order_ids
 */
function handle_bulk_action( $redirect_to, $action, $order_ids ) {
	if ( ACTION !== $action || ! current_user_can( CAPABILITY ) ) {
		return $redirect_to;
	}

	$order_ids = is_array( $order_ids ) ? array_values( array_filter( array_map( 'absint', $order_ids ) ) ) : array();

	if ( empty( $order_ids ) ) {
		return $redirect_to;
	}

	$url = add_query_arg(
		array(
			QUERY_FLAG => '1',
			'ids'      => implode( ',', $order_ids ),
		),
		admin_url( 'admin.php' )
	);

	return wp_nonce_url( $url, NONCE );
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

function maybe_render() {
	if ( empty( $_GET[ QUERY_FLAG ] ) ) {
		return;
	}

	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'אין לך הרשאה להפיק רשימת ליקוט.', 'marcha-pick-list' ), 403 );
	}

	check_admin_referer( NONCE );

	$raw       = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
	$order_ids = array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );

	if ( empty( $order_ids ) ) {
		wp_die( esc_html__( 'לא נבחרו הזמנות.', 'marcha-pick-list' ) );
	}

	render_page( collect_lines( $order_ids ), $order_ids );
	exit;
}

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

			$product    = $item->get_product();
			$variation  = ( $product && $product->is_type( 'variation' ) ) ? $product : null;
			$parent_id  = $variation ? $variation->get_parent_id() : ( $product ? $product->get_id() : 0 );
			$parent     = $parent_id ? wc_get_product( $parent_id ) : null;
			$variant_id = $variation ? $variation->get_id() : 0;
			$key        = $parent_id . ':' . $variant_id;
			$quantity   = (int) $item->get_quantity();

			if ( isset( $lines[ $key ] ) ) {
				$lines[ $key ]['qty']   += $quantity;
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
 */
function compare_lines( $a, $b ) {
	$by_category = strnatcasecmp( (string) $a['category'], (string) $b['category'] );

	if ( 0 !== $by_category ) {
		return $by_category;
	}

	$by_name = strnatcasecmp( (string) $a['name'], (string) $b['name'] );

	if ( 0 !== $by_name ) {
		return $by_name;
	}

	return strnatcasecmp( (string) $a['size'], (string) $b['size'] );
}

/**
 * The size is what the picker actually walks the shelf with, so the variation
 * attributes are preferred and the item meta is the fallback.
 */
function line_size( $item, $variation ) {
	if ( $variation ) {
		$attributes = $variation->get_variation_attributes();
		$values     = array();

		foreach ( $attributes as $value ) {
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

/**
 * @param array<string,array<string,mixed>> $lines
 * @param int[]                             $order_ids
 */
function render_page( $lines, $order_ids ) {
	$total_units = array_sum( wp_list_pluck( $lines, 'qty' ) );
	$order_count = count( $order_ids );
	$printed_at  = wp_date( 'd/m/Y H:i' );

	$grouped = array();

	foreach ( $lines as $line ) {
		$grouped[ $line['category'] ][] = $line;
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php esc_html_e( 'רשימת ליקוט', 'marcha-pick-list' ); ?> · <?php echo esc_html( $printed_at ); ?></title>
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
	margin:0;
	padding:var(--pad);
	background:#f6f7f8;
	color:var(--ink);
	font-family:"Ploni",-apple-system,BlinkMacSystemFont,"Segoe UI",Rubik,Arial,sans-serif;
	font-size:15px;
	line-height:1.5;
}
.sheet{max-width:980px;margin-inline:auto;background:#fff;border-radius:var(--radius);padding:var(--pad);box-shadow:0 1px 3px rgba(0,0,0,.08)}
header.masthead{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:baseline;justify-content:space-between;border-bottom:2px solid var(--ink);padding-bottom:14px;margin-bottom:22px}
h1{margin:0;font-size:clamp(20px,3vw,26px);letter-spacing:-.01em}
.meta{color:var(--muted);font-size:14px;display:flex;gap:18px;flex-wrap:wrap}
.meta b{color:var(--ink);font-weight:700}
.toolbar{display:flex;gap:10px;margin-bottom:20px}
.toolbar button{font:inherit;font-weight:600;cursor:pointer;border:1px solid var(--line);background:#fff;color:var(--ink);padding:9px 18px;border-radius:8px;transition:background .15s ease,border-color .15s ease}
.toolbar button:hover{background:#f3f4f6;border-color:#d1d5db}
.toolbar button.primary{background:var(--accent);border-color:var(--accent);color:#fff}
.toolbar button.primary:hover{background:#115e56}
h2.group{font-size:15px;margin:26px 0 8px;padding:7px 12px;background:#f3f4f6;border-radius:6px;letter-spacing:.01em}
h2.group:first-of-type{margin-top:0}
table{width:100%;border-collapse:collapse}
th,td{text-align:right;padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:middle}
thead th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);border-bottom:1px solid var(--ink);padding-block:6px}
tbody tr:last-child td{border-bottom:1px solid var(--line)}
td.thumb{width:62px}
td.thumb img{display:block;width:54px;height:54px;object-fit:cover;border-radius:6px;background:#f3f4f6}
td.thumb .placeholder{width:54px;height:54px;border-radius:6px;background:#f3f4f6}
td.name{font-weight:600;line-height:1.35}
td.size{white-space:nowrap;font-variant-numeric:tabular-nums}
td.sku{color:var(--muted);font-size:13px;white-space:nowrap}
td.qty{font-size:19px;font-weight:700;text-align:center;width:66px;font-variant-numeric:tabular-nums}
td.orders{color:var(--muted);font-size:12px;max-width:190px}
td.check{width:40px;text-align:center}
td.check span{display:inline-block;width:20px;height:20px;border:2px solid #9ca3af;border-radius:4px}
tfoot td{padding-top:14px;font-weight:700;border:0}
footer.orders-included{margin-top:28px;padding-top:14px;border-top:1px solid var(--line);color:var(--muted);font-size:13px}
.empty{padding:40px 0;text-align:center;color:var(--muted)}
@media print{
	body{background:#fff;padding:0;font-size:12.5px}
	.sheet{box-shadow:none;border-radius:0;max-width:none;padding:0}
	.toolbar{display:none}
	h2.group{background:transparent;border-bottom:1.5px solid var(--ink);border-radius:0;padding-inline:0;break-after:avoid}
	table{break-inside:auto}
	tr{break-inside:avoid;break-after:auto}
	thead{display:table-header-group}
	td.thumb img,td.thumb .placeholder{width:40px;height:40px}
	@page{size:A4;margin:12mm}
}
</style>
</head>
<body>
<main class="sheet">
	<header class="masthead">
		<h1><?php esc_html_e( 'רשימת ליקוט', 'marcha-pick-list' ); ?></h1>
		<div class="meta">
			<span><?php esc_html_e( 'הזמנות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) $order_count ); ?></b></span>
			<span><?php esc_html_e( 'שורות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) count( $lines ) ); ?></b></span>
			<span><?php esc_html_e( 'סה״כ זוגות:', 'marcha-pick-list' ); ?> <b><?php echo esc_html( (string) $total_units ); ?></b></span>
			<span><?php echo esc_html( $printed_at ); ?></span>
		</div>
	</header>

	<div class="toolbar">
		<button type="button" class="primary" onclick="window.print()"><?php esc_html_e( 'הדפסה', 'marcha-pick-list' ); ?></button>
		<button type="button" onclick="window.close()"><?php esc_html_e( 'סגירה', 'marcha-pick-list' ); ?></button>
	</div>

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
								<img src="<?php echo esc_url( $row['image'] ); ?>" alt="" loading="lazy" width="54" height="54">
							<?php else : ?>
								<span class="placeholder"></span>
							<?php endif; ?>
						</td>
						<td class="name"><?php echo esc_html( $row['name'] ); ?></td>
						<td class="size"><?php echo esc_html( $row['size'] ? $row['size'] : '—' ); ?></td>
						<td class="sku"><?php echo esc_html( $row['sku'] ? $row['sku'] : '—' ); ?></td>
						<td class="qty"><?php echo esc_html( (string) $row['qty'] ); ?></td>
						<td class="orders"><?php echo esc_html( implode( ', ', array_unique( $row['orders'] ) ) ); ?></td>
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
</body>
</html>
	<?php
}
