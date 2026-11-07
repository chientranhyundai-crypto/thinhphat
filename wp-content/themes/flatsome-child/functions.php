<?php
/**
 * Flatsome Child — Phase 2–8: homepage, header, Woo helpers, brand, product schema.
 */

defined( 'ABSPATH' ) || exit;

require_once get_stylesheet_directory() . '/inc/business-info.php';
require_once get_stylesheet_directory() . '/inc/product-schema.php';
require_once get_stylesheet_directory() . '/inc/footer.php';
require_once get_stylesheet_directory() . '/inc/contact.php';
require_once get_stylesheet_directory() . '/inc/about.php';
require_once get_stylesheet_directory() . '/inc/demo-data.php';
require_once get_stylesheet_directory() . '/inc/projects.php';
require_once get_stylesheet_directory() . '/inc/solutions.php';
require_once get_stylesheet_directory() . '/inc/news.php';
require_once get_stylesheet_directory() . '/inc/product-filter.php';
require_once get_stylesheet_directory() . '/inc/floating-contact.php';

/**
 * Enqueue homepage foundation CSS only on the front page.
 */
function sa_child_enqueue_homepage_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	$js_rel  = '/assets/js/hero-fit.js';
	$js_path = get_stylesheet_directory() . $js_rel;
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'sa-hero-fit',
			get_stylesheet_directory_uri() . $js_rel,
			array( 'jquery' ),
			(string) filemtime( $js_path ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sa_child_enqueue_homepage_assets', 20 );

/**
 * Body class helper for homepage scoping.
 */
function sa_child_body_class( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'sa-home';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_child_body_class' );

/**
 * Homepage product categories — custom premium cards (no Flatsome image-cover).
 * Dynamic Woo terms only; keeps same category IDs from shortcode.
 *
 * @param array $atts Shortcode atts.
 * @return string
 */
function sa_child_ux_product_categories_safe( $atts = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p class="sa-empty">WooCommerce chưa sẵn sàng cho danh mục sản phẩm.</p>';
	}

	$atts = shortcode_atts(
		array(
			'ids'        => '17,18,19,20,21,22',
			'hide_empty' => '0',
			'orderby'    => 'include',
			'order'      => 'ASC',
		),
		$atts,
		'sa_ux_product_categories'
	);

	$ids = array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) );
	if ( empty( $ids ) ) {
		return '<p class="sa-empty">Chưa có danh mục sản phẩm để hiển thị.</p>';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'include'    => $ids,
			'hide_empty' => ( '1' === (string) $atts['hide_empty'] || 'true' === (string) $atts['hide_empty'] ),
			'orderby'    => 'include',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p class="sa-empty">Chưa có danh mục sản phẩm để hiển thị.</p>';
	}

	// Preserve shortcode ID order.
	$by_id = array();
	foreach ( $terms as $term ) {
		$by_id[ (int) $term->term_id ] = $term;
	}

	$html  = '<div class="sa-woo-cats sa-woo-cats--premium">';
	$html .= '<div class="sa-cat-grid">';

	foreach ( $ids as $tid ) {
		if ( empty( $by_id[ $tid ] ) ) {
			continue;
		}
		$term     = $by_id[ $tid ];
		$link     = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$count    = (int) $term->count;
		$count_lb = sprintf(
			/* translators: %d product count */
			_n( '%d sản phẩm', '%d sản phẩm', $count, 'flatsome-child' ),
			$count
		);

		$img = '';
		if ( $thumb_id ) {
			$img = wp_get_attachment_image(
				$thumb_id,
				'medium_large',
				false,
				array(
					'class'    => 'sa-cat-card__img',
					'alt'      => $term->name,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 1024px) 45vw, 360px',
				)
			);
		}

		$html .= '<a class="sa-cat-card" href="' . esc_url( $link ) . '">';
		$html .= '<span class="sa-cat-card__media">';
		if ( $img ) {
			$html .= $img;
		} else {
			$html .= '<span class="sa-cat-card__img-fallback" aria-hidden="true"></span>';
		}
		$html .= '</span>';
		$html .= '<span class="sa-cat-card__footer">';
		$html .= '<span class="sa-cat-card__text">';
		$html .= '<span class="sa-cat-card__name">' . esc_html( $term->name ) . '</span>';
		$html .= '<span class="sa-cat-card__count">' . esc_html( $count_lb ) . '</span>';
		$html .= '</span>';
		$html .= '<span class="sa-cat-card__arrow" aria-hidden="true"></span>';
		$html .= '</span>';
		$html .= '</a>';
	}

	$html .= '</div></div>';
	return $html;
}
add_shortcode( 'sa_ux_product_categories', 'sa_child_ux_product_categories_safe' );

/**
 * Mark category image wrappers that have a real thumbnail (for CSS fallback).
 */
function sa_child_mark_real_category_images( $html, $category ) {
	if ( ! is_front_page() || ! $category || is_wp_error( $category ) ) {
		return $html;
	}

	$thumb_id = (int) get_term_meta( $category->term_id, 'thumbnail_id', true );
	if ( $thumb_id > 0 && false !== strpos( $html, 'box-image' ) ) {
		$html = str_replace( 'class="box-image', 'class="box-image has-real-image', $html );
	}

	return $html;
}
add_filter( 'woocommerce_subcategory_thumbnail_html', 'sa_child_mark_real_category_images', 20, 2 );

/**
 * Prefer larger category thumbnails on homepage cards.
 *
 * @param string|array $size Size.
 * @return string
 */
function sa_category_thumbnail_size( $size ) {
	if ( is_front_page() ) {
		return 'medium_large';
	}
	return $size;
}
add_filter( 'subcategory_archive_thumbnail_size', 'sa_category_thumbnail_size' );

/**
 * Homepage: featured products — Flickity carousel (Flatsome) + premium cards.
 *
 * @param array $atts Shortcode atts.
 * @return string
 */
function sa_child_ux_featured_products_safe( $atts = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p class="sa-empty">WooCommerce chưa sẵn sàng cho sản phẩm nổi bật.</p>';
	}

	$atts = shortcode_atts(
		array(
			'products' => '8',
		),
		$atts,
		'sa_ux_featured_products'
	);

	$limit = max( 1, min( 12, absint( $atts['products'] ) ) );

	$products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => $limit,
			'featured' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
		)
	);

	if ( empty( $products ) ) {
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
	}

	if ( empty( $products ) ) {
		return '';
	}

	$flickity = wp_json_encode(
		array(
			'imagesLoaded'           => true,
			'groupCells'             => true,
			'cellAlign'              => 'left',
			'wrapAround'             => count( $products ) > 4,
			'prevNextButtons'        => true,
			'percentPosition'        => true,
			'pageDots'               => false,
			'contain'                => true,
			'autoPlay'               => 5500,
			'pauseAutoPlayOnHover'   => true,
			'draggable'              => true,
			'dragThreshold'          => 5,
			'selectedAttraction'     => 0.08,
			'friction'               => 0.28,
		)
	);

	$html  = '<div class="sa-feat-carousel-wrap">';
	$html .= '<div class="row slider row-slider slider-nav-simple slider-nav-dark slider-nav-push sa-feat-carousel" data-flickity-options="' . esc_attr( $flickity ) . '">';

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$html .= '<div class="col large-3 medium-4 small-6 sa-feat-slide">';
		$html .= '<div class="col-inner">';
		$html .= sa_render_featured_product_card( $product );
		$html .= '</div></div>';
	}

	$html .= '</div>';
	$html .= '<div class="sa-feat-carousel__cta">';
	$html .= '<a class="sa-btn sa-btn--primary" href="' . esc_url( home_url( '/san-pham/' ) ) . '">XEM TẤT CẢ SẢN PHẨM</a>';
	$html .= '</div></div>';

	return $html;
}

/**
 * Full Featured Products homepage section — omitted when catalog is empty.
 *
 * @param array $atts Shortcode atts.
 * @return string
 */
function sa_home_featured_section_shortcode( $atts = array() ) {
	$inner = sa_child_ux_featured_products_safe( $atts );
	if ( '' === trim( (string) $inner ) ) {
		return '';
	}

	ob_start();
	?>
	[section label="Featured Products" class="sa-section sa-section--alt sa-featured" padding="0px" padding__sm="0px"]
	[row width="custom" custom_width="1240px"]
	[col span="12" span__sm="12"]
	<div class="sa-section-head is-center">
	<p class="sa-eyebrow">Nổi bật</p>
	<h2 class="sa-title">Sản phẩm nổi bật</h2>
	<p class="sa-lead">Sản phẩm tiêu biểu từ danh mục UPS và thiết bị nguồn điện.</p>
	</div>
	<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	[/col]
	[/row]
	[/section]
	<?php
	return do_shortcode( (string) ob_get_clean() );
}
add_shortcode( 'sa_home_featured_section', 'sa_home_featured_section_shortcode' );

/**
 * Premium featured product card (THỊNH PHÁT) — whole card → PDP.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function sa_render_featured_product_card( $product ) {
	$id        = $product->get_id();
	$permalink = get_permalink( $id );
	$name      = $product->get_name();
	$price     = sa_product_price_label( $product );
	$sku       = $product->get_sku();

	$power_terms = wc_get_product_terms( $id, 'pa_cong-suat', array( 'fields' => 'names' ) );
	$volt_terms  = wc_get_product_terms( $id, 'pa_dien-ap', array( 'fields' => 'names' ) );
	$power       = ( ! is_wp_error( $power_terms ) && ! empty( $power_terms ) ) ? $power_terms[0] : '';
	$voltage     = ( ! is_wp_error( $volt_terms ) && ! empty( $volt_terms ) ) ? $volt_terms[0] : '';
	if ( ! $power ) {
		// Fallback: first segment of key specs.
		$specs = sa_product_key_specs( $product );
		$parts = array_filter( array_map( 'trim', explode( '·', $specs ) ) );
		$power = $parts ? $parts[0] : '';
	}

	$img_id = (int) $product->get_image_id();
	if ( $img_id ) {
		$image = wp_get_attachment_image(
			$img_id,
			'medium_large',
			false,
			array(
				'class'    => 'sa-feat-card__img',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $name,
				'sizes'    => '(max-width: 649px) 70vw, (max-width: 1024px) 33vw, 280px',
			)
		);
	} else {
		$image = '<span class="sa-feat-card__img-fallback" aria-hidden="true"></span>';
	}

	ob_start();
	?>
	<a class="sa-feat-card" href="<?php echo esc_url( $permalink ); ?>">
		<span class="sa-feat-card__media">
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
		<span class="sa-feat-card__badges">
			<span class="sa-feat-card__badge">
				<span class="sa-feat-card__badge-label">Công suất</span>
				<span class="sa-feat-card__badge-value"><?php echo esc_html( $power ? $power : '—' ); ?></span>
			</span>
			<span class="sa-feat-card__badge">
				<span class="sa-feat-card__badge-label">Thông số</span>
				<span class="sa-feat-card__badge-value"><?php echo esc_html( $voltage ? $voltage : '—' ); ?></span>
			</span>
		</span>
		<span class="sa-feat-card__body">
			<span class="sa-feat-card__title"><?php echo esc_html( $name ); ?></span>
			<?php if ( $sku ) : ?>
				<span class="sa-feat-card__sku"><?php echo esc_html( $sku ); ?></span>
			<?php endif; ?>
			<span class="sa-feat-card__price"><?php echo esc_html( $price ); ?></span>
		</span>
	</a>
	<?php
	return (string) ob_get_clean();
}

add_shortcode( 'sa_ux_featured_products', 'sa_child_ux_featured_products_safe' );

/**
 * Inter for /trang-chu-new/ section headings and trust numbers.
 */
function sa_trang_chu_new_fonts() {
	if ( ! is_page( 489 ) ) {
		return;
	}
	wp_enqueue_style(
		'sa-inter',
		'https://fonts.googleapis.com/css2?family=Inter:wght@700&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'sa_trang_chu_new_fonts', 20 );

/**
 * Count-up for the trust numbers on /trang-chu-new/ only.
 */
function sa_trang_chu_new_counter() {
	if ( ! is_page( 489 ) ) {
		return;
	}
	$rel  = '/assets/js/trang-chu-counter.js';
	$path = get_stylesheet_directory() . $rel;
	if ( ! file_exists( $path ) ) {
		return;
	}
	wp_enqueue_script(
		'sa-trang-chu-counter',
		get_stylesheet_directory_uri() . $rel,
		array(),
		(string) filemtime( $path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'sa_trang_chu_new_counter', 20 );

/**
 * Spec row on /trang-chu-new/ featured cards only.
 * Power comes from pa_cong-suat. Warranty is shown only when that attribute exists.
 */
function sa_trang_chu_new_product_specs() {
	if ( ! is_page( 489 ) ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$id    = $product->get_id();
	$power = '';
	$terms = wc_get_product_terms( $id, 'pa_cong-suat', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$power = $terms[0];
	}

	$warranty = '';
	if ( taxonomy_exists( 'pa_bao-hanh' ) ) {
		$wterms = wc_get_product_terms( $id, 'pa_bao-hanh', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $wterms ) && ! empty( $wterms ) ) {
			$warranty = $wterms[0];
		}
	}

	if ( '' === $power && '' === $warranty ) {
		return;
	}

	echo '<div class="masu-spec">';
	if ( '' !== $power ) {
		echo '<div class="masu-spec__box"><span class="masu-spec__label">Công suất</span><span class="masu-spec__value">' . esc_html( $power ) . '</span></div>';
	}
	if ( '' !== $warranty ) {
		echo '<div class="masu-spec__box"><span class="masu-spec__label">Bảo hành</span><span class="masu-spec__value">' . esc_html( $warranty ) . '</span></div>';
	}
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'sa_trang_chu_new_product_specs', 4 );

/**
 * UPS category archives styled like the MASU category layout.
 * Term IDs stay in the database; this only flags the frontend view.
 */
function sa_masu_ups_term_ids() {
	$ids  = array( 75, 76, 77, 78, 79, 80, 17, 18, 19, 20, 21, 22 );
	$term = get_term_by( 'slug', 'bo-luu-dien', 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		$ids[] = (int) $term->term_id;
	}
	return $ids;
}

function sa_is_masu_catalog() {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	return sa_is_masu_ups_cat();
}

function sa_is_masu_ups_cat() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return false;
	}
	$term = get_queried_object();
	if ( ! $term || empty( $term->term_id ) ) {
		return false;
	}
	$ids = sa_masu_ups_term_ids();
	$id  = (int) $term->term_id;
	if ( in_array( $id, $ids, true ) ) {
		return true;
	}
	foreach ( get_ancestors( $id, 'product_cat' ) as $ancestor ) {
		if ( in_array( (int) $ancestor, $ids, true ) ) {
			return true;
		}
	}
	return false;
}

function sa_masu_cat_body_class( $classes ) {
	if ( sa_is_masu_catalog() ) {
		$classes[] = 'sa-masu-cat';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_masu_cat_body_class' );

function sa_masu_cat_fonts() {
	if ( ! sa_is_masu_catalog() ) {
		return;
	}
	wp_enqueue_style(
		'sa-inter',
		'https://fonts.googleapis.com/css2?family=Inter:wght@700&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'sa_masu_cat_fonts', 20 );

function sa_masu_cat_heading_text() {
	$term = get_queried_object();
	if ( $term && isset( $term->slug ) && 'bo-luu-dien' === $term->slug ) {
		return 'Bộ lưu điện (UPS)';
	}
	return function_exists( 'woocommerce_page_title' ) ? woocommerce_page_title( false ) : '';
}

function sa_masu_cat_title() {
	if ( ! sa_is_masu_catalog() ) {
		return;
	}
	$title = sa_masu_cat_heading_text();
	if ( '' === $title ) {
		return;
	}
	echo '<h1 class="shop-page-title sa-masu-cat__title">' . esc_html( $title ) . '</h1>';
}
add_action( 'flatsome_category_title', 'sa_masu_cat_title', 15 );

function sa_masu_cat_breadcrumb( $crumbs ) {
	if ( ! sa_is_masu_ups_cat() || empty( $crumbs ) ) {
		return $crumbs;
	}
	$term = get_queried_object();
	if ( $term && isset( $term->slug ) && 'bo-luu-dien' === $term->slug ) {
		$last = count( $crumbs ) - 1;
		if ( isset( $crumbs[ $last ][0] ) ) {
			$crumbs[ $last ][0] = 'Bộ lưu điện (UPS)';
		}
	}
	return $crumbs;
}
add_filter( 'woocommerce_get_breadcrumb', 'sa_masu_cat_breadcrumb' );

/**
 * Public URL /bo-luu-dien/ for the UPS category.
 */
function sa_bo_luu_dien_rewrite() {
	add_rewrite_rule( '^bo-luu-dien/?$', 'index.php?product_cat=bo-luu-dien', 'top' );
	add_rewrite_rule( '^bo-luu-dien/page/([0-9]{1,})/?$', 'index.php?product_cat=bo-luu-dien&paged=$matches[1]', 'top' );
}
add_action( 'init', 'sa_bo_luu_dien_rewrite' );

function sa_bo_luu_dien_term_link( $url, $term, $taxonomy ) {
	if ( 'product_cat' === $taxonomy && isset( $term->slug ) && 'bo-luu-dien' === $term->slug ) {
		return home_url( '/bo-luu-dien/' );
	}
	return $url;
}
add_filter( 'term_link', 'sa_bo_luu_dien_term_link', 10, 3 );

