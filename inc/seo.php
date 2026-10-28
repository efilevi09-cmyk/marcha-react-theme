<?php
/**
 * Crawlable server-side fallback for the React shell.
 *
 * The visible UI is rendered client-side, so the raw HTML would otherwise contain an empty #root.
 * This prints a plain, semantic summary of the current page (heading, description, products, links)
 * inside #root. React replaces it as soon as it mounts, so shoppers never see it, while crawlers,
 * link-preview bots and no-JS clients get real content.
 */
defined( 'ABSPATH' ) || exit;

function marcha_seo_product_item( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$img = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
	return sprintf(
		'<li><a href="%s">%s<span>%s</span></a> <span>%s</span></li>',
		esc_url( get_permalink( $product->get_id() ) ),
		$img ? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $product->get_name() ) . '" width="150" height="150" loading="lazy">' : '',
		esc_html( $product->get_name() ),
		esc_html( wp_strip_all_tags( html_entity_decode( $product->get_price_html() ) ) )
	);
}

function marcha_seo_products_list( $products ) {
	$out = '';
	foreach ( $products as $p ) {
		$out .= marcha_seo_product_item( $p );
	}
	return $out ? '<ul>' . $out . '</ul>' : '';
}

function marcha_seo_categories() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}
	$out = '<nav aria-label="קטגוריות"><ul>';
	foreach ( $terms as $t ) {
		$out .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
	}
	return $out . '</ul></nav>';
}

function marcha_seo_fallback_html() {
	if ( ! function_exists( 'wc_get_products' ) || marcha_is_native_route() ) {
		return '';
	}
	$html = '';

	if ( is_front_page() ) {
		$html .= '<h1>' . esc_html( get_bloginfo( 'name' ) ) . ' – נעלי בובה ובלרינה</h1>';
		$html .= '<p>' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
		$html .= marcha_seo_categories();
		$html .= '<h2>הכי חם</h2>' . marcha_seo_products_list( wc_get_products( array( 'limit' => 24, 'status' => 'publish', 'orderby' => 'popularity' ) ) );
		$html .= '<h2>חדש באתר</h2>' . marcha_seo_products_list( wc_get_products( array( 'limit' => 24, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) ) );
	} elseif ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$html .= '<h1>' . esc_html( $product->get_name() ) . '</h1>';
			$img   = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			if ( $img ) {
				$html .= '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $product->get_name() ) . '" width="600" height="600">';
			}
			$html .= '<p>' . esc_html( wp_strip_all_tags( html_entity_decode( $product->get_price_html() ) ) ) . '</p>';
			$html .= '<p>' . esc_html( $product->is_in_stock() ? 'במלאי' : 'אזל מהמלאי' ) . '</p>';
			$desc  = wp_strip_all_tags( $product->get_short_description() . ' ' . $product->get_description() );
			if ( trim( $desc ) ) {
				$html .= '<p>' . esc_html( trim( $desc ) ) . '</p>';
			}
			foreach ( $product->get_attributes() as $attr ) {
				if ( $attr instanceof WC_Product_Attribute && $attr->get_visible() ) {
					$val = $product->get_attribute( $attr->get_name() );
					if ( $val ) {
						$html .= '<p>' . esc_html( wc_attribute_label( $attr->get_name() ) ) . ': ' . esc_html( $val ) . '</p>';
					}
				}
			}
			$cats = get_the_terms( $product->get_id(), 'product_cat' );
			if ( $cats && ! is_wp_error( $cats ) ) {
				$html .= '<p>';
				foreach ( $cats as $c ) {
					$html .= '<a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a> ';
				}
				$html .= '</p>';
			}
		}
	} elseif ( is_product_taxonomy() || is_shop() ) {
		$title = is_shop() ? 'כל המוצרים' : single_term_title( '', false );
		$html .= '<h1>' . esc_html( $title ) . '</h1>';
		$term = get_queried_object();
		if ( $term instanceof WP_Term && $term->description ) {
			$html .= '<p>' . esc_html( wp_strip_all_tags( $term->description ) ) . '</p>';
		}
		$html .= marcha_seo_categories();
		$items = array();
		global $wp_query;
		foreach ( (array) $wp_query->posts as $post ) {
			$p = wc_get_product( $post->ID );
			if ( $p ) {
				$items[] = $p;
			}
		}
		$html .= marcha_seo_products_list( $items );
	} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
		$html .= '<h1>עגלת קניות</h1>';
	}

	if ( ! $html ) {
		return '';
	}
	// Visually hidden but present in the DOM; replaced by React on mount.
	return '<div id="seo-fallback" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap">' . $html . '</div>';
}
