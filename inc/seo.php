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
		if ( $term instanceof WP_Term ) {
			$html .= wp_kses_post( (string) get_term_meta( $term->term_id, 'marcha_seo_content', true ) );
		}
	} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
		$html .= '<h1>עגלת קניות</h1>';
	}

	if ( ! $html ) {
		return '';
	}
	// Visually hidden but present in the DOM; replaced by React on mount.
	return '<div id="seo-fallback" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap">' . $html . '</div>';
}

/**
 * Product structured data.
 *
 * WooCommerce builds its Product JSON-LD from hooks that fire inside the single-product
 * template, and the React shell replaces that template, so product pages were going out
 * with no price, availability or rating for Google to read - no rich result, no stars,
 * no price in the listing. Asking WooCommerce to generate its own data here keeps the
 * markup identical to a stock shop; its existing wp_footer printer still emits it.
 *
 * Yoast owns the rest of the graph (WebSite, Organization, BreadcrumbList) and does not
 * emit Product without its paid WooCommerce add-on, so nothing is duplicated.
 */
add_action(
	'wp',
	function () {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! isset( WC()->structured_data ) || ! WC()->structured_data ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			WC()->structured_data->generate_product_data( $product );
		}
	},
	20
);

/**
 * Long-form buying guidance under a category's product grid.
 *
 * The category pages are what compete for the broad searches ("נעלי בובה",
 * "נעלי בלרינה"), and a grid of thumbnails gives a search engine almost nothing to
 * read. This holds real guidance - sizing, materials, how to choose - as term meta,
 * editable from the category screen and through the REST API.
 *
 * It renders inside the React page rather than only in the crawler fallback, so what
 * Google indexes is what a shopper sees.
 */
add_action(
	'init',
	function () {
		register_term_meta(
			'product_cat',
			'marcha_seo_content',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'description'       => 'Buying guidance shown under the product grid.',
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
	}
);

/** Hand it to the shop front alongside the category's name and description. */
add_action(
	'woocommerce_init',
	function () {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' )
			|| ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\ProductCategorySchema' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\ProductCategorySchema::IDENTIFIER,
				'namespace'       => 'marcha',
				'data_callback'   => function ( $term ) {
					$id = is_object( $term ) && isset( $term->term_id ) ? (int) $term->term_id : (int) $term;
					return array( 'seo_content' => (string) get_term_meta( $id, 'marcha_seo_content', true ) );
				},
				'schema_callback' => function () {
					return array(
						'seo_content' => array(
							'description' => 'Buying guidance shown under the product grid.',
							'type'        => 'string',
							'readonly'    => true,
						),
					);
				},
				'schema_type'     => ARRAY_A,
			)
		);
	}
);

/**
 * Meta titles and descriptions for the buying guides.
 *
 * Yoast does not expose these fields over the REST API, so the copy lives here instead of
 * being typed into the editor. The map is keyed by page slug, and each filter steps aside
 * the moment a value is saved in the Yoast box — so anything written there still wins.
 */
function marcha_guide_meta() {
	return array(
		'מדריך-מידות'         => array(
			'title' => 'מדריך מידות לנעלי בלרינה – טבלת מידות מלאה | מרשה',
			'desc'  => 'איך מודדים כף רגל נכון, טבלת מידות מלאה EU/US/UK, מה עושים בין שתי מידות ולמה בנעלי שפיץ המידה עובדת אחרת.',
		),
		'נעלי-כלה-שטוחות'     => array(
			'title' => 'נעלי כלה שטוחות – מדריך לבחירת נעלי בלרינה לחתונה | מרשה',
			'desc'  => 'איזה גוון, איזו גזרה ואיך לא לטעות במידה. מדריך לכלות שרוצות נעל שטוחה שמחזיקה מהחופה ועד סוף הריקודים.',
		),
		'נעלי-בובה-לילדות'    => array(
			'title' => 'נעלי בובה לילדות – מדריך מידות, חומרים ודגמים | מרשה',
			'desc'  => 'למה לא קונים "לגדול לתוכן", מה המרווח הנכון בין כף הרגל לנעל, ואיזה חומר מחזיק מעמד אצל ילדות. מידות 22 עד 43.',
		),
		'איך-לבחור-נעלי-בובה' => array(
			'title' => 'איך לבחור נעלי בובה שנוח ללכת בהן כל היום | מרשה',
			'desc'  => 'שבעה דברים שקובעים אם זוג נעלי בובה יישאר בארון או ייצא כל יום: מידה, רוחב הרגל, חומר, גזרה וצבע.',
		),
	);
}

/** The guide entry for the page being viewed, or null. */
function marcha_current_guide_meta() {
	if ( is_admin() || ! is_page() ) {
		return null;
	}
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return null;
	}
	$map  = marcha_guide_meta();
	$slug = urldecode( $page->post_name );
	return isset( $map[ $slug ] ) ? $map[ $slug ] + array( 'id' => $page->ID ) : null;
}

add_filter(
	'wpseo_title',
	function ( $title ) {
		$guide = marcha_current_guide_meta();
		if ( ! $guide || get_post_meta( $guide['id'], '_yoast_wpseo_title', true ) ) {
			return $title; // a title typed in the Yoast box wins
		}
		return $guide['title'];
	},
	20
);

add_filter(
	'wpseo_metadesc',
	function ( $desc ) {
		$guide = marcha_current_guide_meta();
		if ( ! $guide || get_post_meta( $guide['id'], '_yoast_wpseo_metadesc', true ) ) {
			return $desc;
		}
		return $guide['desc'];
	},
	20
);

/** Without Yoast the same copy still reaches the head. */
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return;
		}
		$guide = marcha_current_guide_meta();
		if ( $guide ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $guide['desc'] ) );
		}
	},
	1
);

/**
 * Search snippets for product pages.
 *
 * Yoast shipped every product the same sentence — the product's name, then "במרשה בלרינה
 * תמצאו מבחר עצום של נעלי בלרינה מכל הסוגים הגדלים והצבעים" — so a shopper searching for
 * black flats read a line that never mentioned black, or flats, or a price, and clicked the
 * result below instead. Google quotes this line verbatim under the link, and it is the only
 * thing she has to go on, so it is built here from the product's own facts.
 */

/** Smallest and largest size the product actually sells, or null when it has no size axis. */
function marcha_product_size_range( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return null;
	}
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_variation() ) {
			continue;
		}
		$labels = $attribute->is_taxonomy()
			? wp_list_pluck( (array) wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) ), 'name' )
			: (array) $attribute->get_options();
		// Sizes are numbers; a colour or material attribute drops out here on its own.
		$sizes = array_filter( array_map( 'intval', $labels ) );
		if ( count( $sizes ) >= 2 ) {
			return array( min( $sizes ), max( $sizes ) );
		}
	}

	return null;
}

/** "119 ₪" or "119–139 ₪", with no trailing zeros. */
function marcha_product_price_text( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	if ( $product->is_type( 'variable' ) ) {
		$min = (float) $product->get_variation_price( 'min', true );
		$max = (float) $product->get_variation_price( 'max', true );
	} else {
		$min = (float) wc_get_price_to_display( $product );
		$max = $min;
	}
	if ( $min <= 0 ) {
		return '';
	}
	$tidy = static function ( $n ) {
		return rtrim( rtrim( number_format( (float) $n, 2, '.', '' ), '0' ), '.' );
	};

	return $min === $max ? $tidy( $min ) . ' ₪' : $tidy( $min ) . '–' . $tidy( $max ) . ' ₪';
}

/**
 * The line Google prints under the link.
 *
 * Kept under 160 characters, because Google cuts the rest off mid-word.
 */
function marcha_product_metadesc( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$facts = array();
	$sizes = marcha_product_size_range( $product );
	if ( $sizes ) {
		$facts[] = sprintf( 'מידות %d–%d', $sizes[0], $sizes[1] );
	}
	$price = marcha_product_price_text( $product );
	if ( $price ) {
		$facts[] = $price;
	}
	$tail = '. משלוח מהיר לכל הארץ ואיסוף מירושלים.';
	$name = trim( wp_strip_all_tags( html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ) ) );
	$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
	$lead = $facts ? $name . ' – ' . implode( ', ', $facts ) : $name;

	// Trim the product name rather than the facts: the sizes and the price are what win the click.
	$over = mb_strlen( $lead . $tail, 'UTF-8' ) - 160;
	if ( $over > 0 && mb_strlen( $name, 'UTF-8' ) > $over + 12 ) {
		$name = rtrim( mb_substr( $name, 0, mb_strlen( $name, 'UTF-8' ) - $over - 1, 'UTF-8' ) ) . '…';
		$lead = $facts ? $name . ' – ' . implode( ', ', $facts ) : $name;
	}

	return $lead . $tail;
}

/** The product currently being viewed, or null anywhere else. */
function marcha_current_product() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return null;
	}
	$product = wc_get_product( get_queried_object_id() );

	return $product instanceof WC_Product ? $product : null;
}

add_filter(
	'wpseo_metadesc',
	function ( $desc ) {
		$product = marcha_current_product();
		if ( ! $product || get_post_meta( $product->get_id(), '_yoast_wpseo_metadesc', true ) ) {
			return $desc; // a description typed into the Yoast box wins
		}
		return marcha_product_metadesc( $product );
	},
	20
);

/** Hebrew brand suffix: the queries that reach these pages are Hebrew, and so is the shop. */
add_filter(
	'wpseo_title',
	function ( $title ) {
		if ( ! marcha_current_product() ) {
			return $title;
		}
		return preg_replace( '/\s*[-–|]\s*Marcha Ballerina\s*$/u', ' | מרשה בלרינה', $title );
	},
	20
);

/** Without Yoast the same line still reaches the head. */
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return;
		}
		$product = marcha_current_product();
		if ( $product ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( marcha_product_metadesc( $product ) ) );
		}
	},
	1
);

/**
 * Fill out the product's structured data.
 *
 * WooCommerce already sends the price, the stock state and the rating. It sends no brand, and
 * only the single featured image — which is why a search for black flats showed a beige shoe
 * and two rubber boots beside the listing. Handing Google the whole gallery lets it pick the
 * picture that matches what was typed.
 */
add_filter(
	'woocommerce_structured_data_product',
	function ( $markup, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $markup;
		}
		if ( empty( $markup['brand'] ) ) {
			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => 'מרשה בלרינה',
			);
		}
		$ids    = array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() );
		$images = array();
		foreach ( array_unique( array_filter( array_map( 'intval', $ids ) ) ) as $id ) {
			$url = wp_get_attachment_image_url( $id, 'full' );
			if ( $url ) {
				$images[] = $url;
			}
		}
		if ( count( $images ) > 1 ) {
			$markup['image'] = array_slice( $images, 0, 8 );
		}

		return $markup;
	},
	10,
	2
);
