<?php
/**
 * Shop product filter. WooCommerce attribute taxonomies + native layered-nav query vars.
 * Does not alter product card markup.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filter groups. Power ranges use pa_dai-cong-suat so pa_cong-suat stays the spec shown on cards.
 *
 * @return array<string,array<string,mixed>>
 */
function sa_filter_groups() {
	return array(
		'thuong-hieu'   => array(
			'label'  => 'Thương hiệu',
			'search' => true,
			'more'   => 6,
			'terms'  => array(
				'hyundai'   => 'Hyundai',
				'tp-series' => 'TP Series',
			),
			'dynamic' => true,
		),
		'price'         => array(
			'label' => 'Khoảng giá',
			'type'  => 'price',
		),
		'so-pha'        => array(
			'label' => 'Số pha',
			'terms' => array(
				'1-pha' => '1 pha',
				'3-pha' => '3 pha',
			),
		),
		'dai-cong-suat' => array(
			'label' => 'Công suất',
			'terms' => array(
				'duoi-5kw'     => 'Dưới 5kW',
				'5-10kw'       => '5–10kW',
				'10-30kva'     => '10–30kVA',
				'30-100kva'    => '30–100kVA',
				'100-300kva'   => '100–300kVA',
				'300-1000kva'  => '300–1000kVA',
				'tren-1000kva' => 'Trên 1000kVA',
			),
		),
	);
}

/**
 * Shop/category archives that show the filter.
 */
function sa_filter_is_view() {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && ! is_admin();
}

/**
 * Create attributes and seed terms once.
 */
function sa_filter_install() {
	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return;
	}

	$existing = array();
	foreach ( wc_get_attribute_taxonomies() as $tax ) {
		$existing[ $tax->attribute_name ] = true;
	}

	foreach ( sa_filter_groups() as $slug => $group ) {
		if ( 'price' === $slug || isset( $existing[ $slug ] ) ) {
			continue;
		}
		$id = wc_create_attribute(
			array(
				'name'         => $group['label'],
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				array( 'product' ),
				array(
					'labels'       => array( 'name' => $group['label'] ),
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			);
		}
	}

	delete_transient( 'wc_attribute_taxonomies' );

	if ( get_option( 'sa_filter_terms_v' ) === '2' ) {
		return;
	}

	$order = 0;
	foreach ( sa_filter_groups() as $slug => $group ) {
		if ( empty( $group['terms'] ) ) {
			continue;
		}
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}
		foreach ( $group['terms'] as $term_slug => $name ) {
			$order++;
			$term = term_exists( $term_slug, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $name, $taxonomy, array( 'slug' => $term_slug ) );
			}
			if ( is_wp_error( $term ) || empty( $term['term_id'] ) ) {
				continue;
			}
			if ( function_exists( 'wc_set_term_order' ) ) {
				wc_set_term_order( (int) $term['term_id'], $order, $taxonomy );
			}
		}
	}

	update_option( 'sa_filter_terms_v', '2', false );
}
add_action( 'init', 'sa_filter_install', 22 );

/**
 * kW/kVA value from a product power label. Ah and other non-power terms return null.
 *
 * @param string $label Term name such as "10kVA" or "1000VA / 800W".
 */
function sa_filter_kw_from_label( $label ) {
	$label = str_replace( array( ',', '–', '—' ), array( '.', '-', '-' ), (string) $label );
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*kva\b/i', $label, $match ) ) {
		return (float) $match[1];
	}
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*va\b/i', $label, $match ) ) {
		return (float) $match[1] / 1000;
	}
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*kw\b/i', $label, $match ) ) {
		return (float) $match[1];
	}
	if ( preg_match( '/(\d+(?:\.\d+)?)\s*w\b/i', $label, $match ) ) {
		return (float) $match[1] / 1000;
	}
	return null;
}

/**
 * Map a kW/kVA figure onto the existing pa_dai-cong-suat slug.
 *
 * @param float $kw Power in kW or kVA.
 */
function sa_filter_power_bucket( $kw ) {
	if ( $kw < 5 ) {
		return 'duoi-5kw';
	}
	if ( $kw < 10 ) {
		return '5-10kw';
	}
	if ( $kw < 30 ) {
		return '10-30kva';
	}
	if ( $kw < 100 ) {
		return '30-100kva';
	}
	if ( $kw < 300 ) {
		return '100-300kva';
	}
	if ( $kw <= 1000 ) {
		return '300-1000kva';
	}
	return 'tren-1000kva';
}

/**
 * Assign pa_dai-cong-suat from each product's pa_cong-suat once, so range filters match live specs.
 */
function sa_filter_sync_power_ranges() {
	if ( '1' === get_option( 'sa_filter_power_sync_v' ) ) {
		return;
	}
	if ( ! taxonomy_exists( 'pa_cong-suat' ) || ! taxonomy_exists( 'pa_dai-cong-suat' ) ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	foreach ( $ids as $product_id ) {
		$terms   = wp_get_post_terms( (int) $product_id, 'pa_cong-suat' );
		$buckets = array();
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			$kw = sa_filter_kw_from_label( $term->name );
			if ( null === $kw ) {
				continue;
			}
			$buckets[] = sa_filter_power_bucket( $kw );
		}
		$buckets = array_values( array_unique( $buckets ) );
		if ( $buckets ) {
			wp_set_object_terms( (int) $product_id, $buckets, 'pa_dai-cong-suat', true );
		}
	}
	update_option( 'sa_filter_power_sync_v', '1', false );
}
add_action( 'init', 'sa_filter_sync_power_ranges', 23 );

/**
 * @return array<string,array<int,string>>
 */
function sa_filter_selected() {
	$selected = array();
	foreach ( sa_filter_groups() as $slug => $group ) {
		if ( 'price' === ( $group['type'] ?? '' ) ) {
			continue;
		}
		$key = 'filter_' . $slug;
		if ( empty( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) {
			continue;
		}
		$terms = array_values( array_filter( array_map( 'sanitize_title', explode( ',', wc_clean( wp_unslash( $_GET[ $key ] ) ) ) ) ) );
		if ( $terms ) {
			$selected[ $slug ] = $terms;
		}
	}
	return $selected;
}

/**
 * @return array{min:float,max:float}
 */
function sa_filter_price_bounds() {
	static $bounds = null;
	if ( null !== $bounds ) {
		return $bounds;
	}
	global $wpdb;
	$row = $wpdb->get_row(
		"SELECT MIN(CAST(pm.meta_value AS DECIMAL(12,2))) AS min_price, MAX(CAST(pm.meta_value AS DECIMAL(12,2))) AS max_price
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '_price' AND pm.meta_value <> '' AND CAST(pm.meta_value AS DECIMAL(12,2)) > 0
		AND p.post_type = 'product' AND p.post_status = 'publish'"
	);
	$min = $row && null !== $row->min_price ? (float) $row->min_price : 0;
	$max = $row && null !== $row->max_price ? (float) $row->max_price : 0;
	if ( $max < $min ) {
		$max = $min;
	}
	$bounds = array(
		'min' => $min,
		'max' => $max,
	);
	return $bounds;
}

/**
 * Active price, or nulls when the control is still on the catalog bounds.
 *
 * @return array{0:?float,1:?float}
 */
function sa_filter_price_selected() {
	$bounds = sa_filter_price_bounds();
	$min    = isset( $_GET['min_price'] ) ? (float) wp_unslash( $_GET['min_price'] ) : $bounds['min'];
	$max    = isset( $_GET['max_price'] ) ? (float) wp_unslash( $_GET['max_price'] ) : $bounds['max'];
	if ( $min < $bounds['min'] ) {
		$min = $bounds['min'];
	}
	if ( $max > $bounds['max'] || $max <= 0 ) {
		$max = $bounds['max'];
	}
	if ( $min > $max ) {
		$swap = $min;
		$min  = $max;
		$max  = $swap;
	}
	return array( $min, $max );
}

function sa_filter_price_is_active() {
	$bounds = sa_filter_price_bounds();
	list( $min, $max ) = sa_filter_price_selected();
	return $min > $bounds['min'] || $max < $bounds['max'];
}

function sa_filter_money( $amount ) {
	return number_format( (float) $amount, 0, ',', '.' ) . '₫';
}

/**
 * Term counts for one attribute, respecting every other active filter.
 *
 * @return array<string,int>
 */
function sa_filter_term_counts( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$taxonomy  = wc_attribute_taxonomy_name( $slug );
	$tax_query = array( 'relation' => 'AND' );
	$tax_query[] = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => array( 'exclude-from-catalog' ),
		'operator' => 'NOT IN',
	);

	foreach ( sa_filter_selected() as $other => $terms ) {
		if ( $other === $slug ) {
			continue;
		}
		$tax_query[] = array(
			'taxonomy' => wc_attribute_taxonomy_name( $other ),
			'field'    => 'slug',
			'terms'    => $terms,
			'operator' => 'IN',
		);
	}

	$meta_query = array();
	if ( sa_filter_price_is_active() ) {
		list( $min, $max ) = sa_filter_price_selected();
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => array( $min, $max ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => $tax_query,
			'meta_query'             => $meta_query,
		)
	);

	$map = array();
	$ids = array_map( 'absint', $query->posts );
	if ( $ids && taxonomy_exists( $taxonomy ) ) {
		global $wpdb;
		$in   = implode( ',', $ids );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.slug, COUNT(DISTINCT tr.object_id) AS cnt
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
				INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				WHERE tr.object_id IN ($in)
				GROUP BY t.slug",
				$taxonomy
			)
		);
		foreach ( $rows as $row ) {
			$map[ $row->slug ] = (int) $row->cnt;
		}
	}

	$cache[ $slug ] = $map;
	return $map;
}

/**
 * @return array<int,WP_Term>
 */
function sa_filter_terms_for( $slug, $group ) {
	$taxonomy = wc_attribute_taxonomy_name( $slug );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => ! empty( $group['dynamic'] ),
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$seed = array_keys( $group['terms'] ?? array() );
	usort(
		$terms,
		static function ( $a, $b ) use ( $seed ) {
			$ia = array_search( $a->slug, $seed, true );
			$ib = array_search( $b->slug, $seed, true );
			$ia = false === $ia ? 1000 : $ia;
			$ib = false === $ib ? 1000 : $ib;
			if ( $ia === $ib ) {
				return strcasecmp( $a->name, $b->name );
			}
			return $ia <=> $ib;
		}
	);

	if ( empty( $group['dynamic'] ) ) {
		$allowed = array_flip( $seed );
		$terms   = array_values(
			array_filter(
				$terms,
				static function ( $term ) use ( $allowed ) {
					return isset( $allowed[ $term->slug ] );
				}
			)
		);
	}

	return $terms;
}

function sa_filter_active_count() {
	$count = 0;
	foreach ( sa_filter_selected() as $terms ) {
		$count += count( $terms );
	}
	if ( sa_filter_price_is_active() ) {
		$count++;
	}
	return $count;
}

function sa_filter_sidebar_html() {
	$selected = sa_filter_selected();
	$bounds   = sa_filter_price_bounds();
	list( $min, $max ) = sa_filter_price_selected();
	$found    = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;

	ob_start();
	?>
	<form id="sa-filter-form" class="sa-filter" action="<?php echo esc_url( sa_filter_base_url() ); ?>" method="get">
		<div class="sa-filter__head">
			<strong>Bộ lọc</strong>
			<button type="button" class="sa-filter__close" data-sa-close aria-label="Đóng bộ lọc">×</button>
		</div>
		<div class="sa-filter__body">
			<?php foreach ( sa_filter_groups() as $slug => $group ) : ?>
				<?php
				$open = in_array( $slug, array( 'nhien-lieu', 'thuong-hieu', 'price' ), true ) || ! empty( $selected[ $slug ] );
				if ( 'price' === ( $group['type'] ?? '' ) && sa_filter_price_is_active() ) {
					$open = true;
				}
				?>
				<section class="sa-filter__group<?php echo $open ? ' is-open' : ''; ?>" data-group="<?php echo esc_attr( $slug ); ?>">
					<button type="button" class="sa-filter__toggle" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>">
						<span><?php echo esc_html( $group['label'] ); ?></span>
						<span class="sa-filter__chevron" aria-hidden="true"></span>
					</button>
					<div class="sa-filter__panel">
						<?php if ( 'price' === ( $group['type'] ?? '' ) ) : ?>
							<?php echo sa_filter_price_html( $bounds, $min, $max ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<?php echo sa_filter_terms_html( $slug, $group, $selected[ $slug ] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<div class="sa-filter__footer">
			<button type="button" class="sa-filter__clear" data-sa-clear>Xóa bộ lọc</button>
			<button type="button" class="sa-filter__apply" data-sa-close>Xem <span data-sa-found><?php echo esc_html( (string) $found ); ?></span> sản phẩm</button>
		</div>
	</form>
	<?php
	return (string) ob_get_clean();
}

function sa_filter_terms_html( $slug, $group, $chosen ) {
	$terms  = sa_filter_terms_for( $slug, $group );
	$counts = sa_filter_term_counts( $slug );
	$more   = (int) ( $group['more'] ?? 0 );
	$name   = 'filter_' . $slug;

	ob_start();
	if ( ! empty( $group['search'] ) ) {
		echo '<input type="search" class="sa-filter__search" placeholder="Tìm thương hiệu" aria-label="Tìm thương hiệu" />';
	}
	echo '<ul class="sa-filter__list">';
	$index = 0;
	foreach ( $terms as $term ) {
		$index++;
		$count = $counts[ $term->slug ] ?? 0;
		$extra = ( $more && $index > $more ) ? ' data-extra="1" hidden' : '';
		echo '<li' . $extra . ' data-name="' . esc_attr( sa_filter_lower( $term->name ) ) . '">';
		echo '<label class="sa-filter__check">';
		echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $term->slug ) . '"' . checked( in_array( $term->slug, $chosen, true ), true, false ) . ' />';
		echo '<span class="sa-filter__box" aria-hidden="true"></span>';
		echo '<span class="sa-filter__name">' . esc_html( $term->name ) . '</span>';
		echo '<span class="sa-filter__count">' . esc_html( (string) $count ) . '</span>';
		echo '</label></li>';
	}
	echo '</ul>';
	if ( $more && count( $terms ) > $more ) {
		echo '<button type="button" class="sa-filter__more" data-sa-more>Xem thêm</button>';
	}
	return (string) ob_get_clean();
}

function sa_filter_price_html( $bounds, $min, $max ) {
	$min_i = (int) round( $min );
	$max_i = (int) round( $max );
	$bmin  = (int) round( $bounds['min'] );
	$bmax  = (int) round( $bounds['max'] );
	ob_start();
	?>
	<div class="sa-filter__price" data-bound-min="<?php echo esc_attr( (string) $bmin ); ?>" data-bound-max="<?php echo esc_attr( (string) $bmax ); ?>">
		<div class="sa-filter__ranges">
			<input type="range" data-sa-range="min" min="<?php echo esc_attr( (string) $bmin ); ?>" max="<?php echo esc_attr( (string) $bmax ); ?>" step="100000" value="<?php echo esc_attr( (string) $min_i ); ?>" aria-label="Giá tối thiểu" />
			<input type="range" data-sa-range="max" min="<?php echo esc_attr( (string) $bmin ); ?>" max="<?php echo esc_attr( (string) $bmax ); ?>" step="100000" value="<?php echo esc_attr( (string) $max_i ); ?>" aria-label="Giá tối đa" />
		</div>
		<div class="sa-filter__price-fields">
			<label>
				<span>Từ</span>
				<input type="number" data-sa-min inputmode="numeric" min="<?php echo esc_attr( (string) $bmin ); ?>" max="<?php echo esc_attr( (string) $bmax ); ?>" value="<?php echo esc_attr( (string) $min_i ); ?>" />
			</label>
			<label>
				<span>Đến</span>
				<input type="number" data-sa-max inputmode="numeric" min="<?php echo esc_attr( (string) $bmin ); ?>" max="<?php echo esc_attr( (string) $bmax ); ?>" value="<?php echo esc_attr( (string) $max_i ); ?>" />
			</label>
		</div>
		<p class="sa-filter__price-readout"><?php echo esc_html( sa_filter_money( $min_i ) . ' – ' . sa_filter_money( $max_i ) ); ?></p>
	</div>
	<?php
	return (string) ob_get_clean();
}

function sa_filter_toolbar_html() {
	global $wp_query;
	$found    = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
	$active   = sa_filter_active_count();
	$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	$options  = array(
		''            => 'Sắp xếp',
		'date'        => 'Mới nhất',
		'price'       => 'Giá tăng dần',
		'price-desc'  => 'Giá giảm dần',
		'popularity'  => 'Phổ biến',
	);

	ob_start();
	?>
	<div id="sa-filter-toolbar" class="sa-filter-toolbar">
		<div class="sa-filter-toolbar__bar">
			<button type="button" class="sa-filter-toolbar__open" data-sa-open>
				Bộ lọc<?php echo $active ? ' (' . esc_html( (string) $active ) . ')' : ''; ?>
			</button>
			<label class="sa-filter-toolbar__sort">
				<span class="screen-reader-text">Sắp xếp</span>
				<select data-sa-orderby>
					<?php foreach ( $options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $orderby, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<div class="sa-filter-chips">
			<?php echo sa_filter_chips_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="sa-filter-toolbar__count"><span data-sa-found><?php echo esc_html( (string) $found ); ?></span> sản phẩm</p>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function sa_filter_chips_html() {
	$selected = sa_filter_selected();
	if ( ! $selected && ! sa_filter_price_is_active() ) {
		return '';
	}
	$html = '';
	foreach ( sa_filter_groups() as $slug => $group ) {
		if ( empty( $selected[ $slug ] ) ) {
			continue;
		}
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		foreach ( $selected[ $slug ] as $term_slug ) {
			$term = get_term_by( 'slug', $term_slug, $taxonomy );
			$name = $term && ! is_wp_error( $term ) ? $term->name : $term_slug;
			$html .= '<button type="button" class="sa-filter-chip" data-clear-filter="' . esc_attr( $slug ) . '" data-clear-value="' . esc_attr( $term_slug ) . '">' . esc_html( $name ) . ' <span aria-hidden="true">×</span></button>';
		}
	}
	if ( sa_filter_price_is_active() ) {
		list( $min, $max ) = sa_filter_price_selected();
		$html .= '<button type="button" class="sa-filter-chip" data-clear-price="1">' . esc_html( sa_filter_money( $min ) . ' – ' . sa_filter_money( $max ) ) . ' <span aria-hidden="true">×</span></button>';
	}
	$html .= '<button type="button" class="sa-filter-chip sa-filter-chip--all" data-sa-clear>Xóa tất cả</button>';
	return $html;
}

function sa_filter_results_html() {
	ob_start();
	echo '<div id="sa-shop-results">';
	if ( woocommerce_product_loop() ) {
		do_action( 'woocommerce_before_shop_loop' );
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
		do_action( 'woocommerce_after_shop_loop' );
	} else {
		do_action( 'woocommerce_no_products_found' );
	}
	echo '</div>';
	return (string) ob_get_clean();
}

function sa_filter_base_url() {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? get_permalink( wc_get_page_id( 'shop' ) ) : $link;
	}
	return get_permalink( wc_get_page_id( 'shop' ) );
}

function sa_filter_lower( $text ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

function sa_filter_body_class( $classes ) {
	if ( sa_filter_is_view() ) {
		$classes[] = 'sa-shop-filter';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_filter_body_class' );

function sa_filter_assets() {
	$home = is_front_page();
	if ( ! sa_filter_is_view() && ! $home ) {
		return;
	}
	if ( sa_filter_is_view() ) {
		$js = get_stylesheet_directory() . '/assets/js/shop-filter.js';
		wp_enqueue_script( 'sa-shop-filter', get_stylesheet_directory_uri() . '/assets/js/shop-filter.js', array(), (string) filemtime( $js ), true );
	}
	if ( $home ) {
		$home_js = get_stylesheet_directory() . '/assets/js/home-catalog.js';
		if ( file_exists( $home_js ) ) {
			wp_enqueue_script( 'sa-home-catalog', get_stylesheet_directory_uri() . '/assets/js/home-catalog.js', array(), (string) filemtime( $home_js ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'sa_filter_assets', 30 );

function sa_filter_fragment() {
	if ( empty( $_GET['sa_filter_fragment'] ) || ! sa_filter_is_view() ) {
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex', true );
	wp_send_json_success(
		array(
			'sidebar' => sa_filter_sidebar_html(),
			'toolbar' => sa_filter_toolbar_html(),
			'results' => sa_filter_results_html(),
			'found'   => isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0,
		)
	);
}
add_action( 'wp', 'sa_filter_fragment' );

function sa_product_filter_sidebar_html() {
	return sa_filter_sidebar_html();
}

function sa_product_filter_toolbar_html() {
	return sa_filter_toolbar_html();
}

function sa_product_filter_results_html() {
	return sa_filter_results_html();
}

/**
 * Product query shared by the homepage catalog grid.
 *
 * @return array{products:array<int,WC_Product>,total:int}
 */
function sa_home_catalog_products() {
	$tax_query = array( 'relation' => 'AND' );
	foreach ( sa_filter_selected() as $slug => $terms ) {
		$tax_query[] = array(
			'taxonomy' => wc_attribute_taxonomy_name( $slug ),
			'field'    => 'slug',
			'terms'    => $terms,
			'operator' => 'IN',
		);
	}

	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	$args    = array(
		'status'   => 'publish',
		'limit'    => 24,
		'paginate' => true,
		'orderby'  => 'date',
		'order'    => 'DESC',
	);
	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query;
	}
	if ( sa_filter_price_is_active() ) {
		list( $min, $max ) = sa_filter_price_selected();
		$args['min_price'] = $min;
		$args['max_price'] = $max;
	}
	if ( 'price' === $orderby ) {
		$args['orderby'] = 'price';
		$args['order']   = 'ASC';
	} elseif ( 'price-desc' === $orderby ) {
		$args['orderby'] = 'price';
		$args['order']   = 'DESC';
	} elseif ( 'popularity' === $orderby ) {
		$args['orderby'] = 'popularity';
	}

	$result = wc_get_products( $args );
	if ( is_object( $result ) && isset( $result->products ) ) {
		return array(
			'products' => $result->products,
			'total'    => (int) $result->total,
		);
	}
	return array(
		'products' => is_array( $result ) ? $result : array(),
		'total'    => is_array( $result ) ? count( $result ) : 0,
	);
}

/**
 * Product card grid. Reuses the existing homepage card markup.
 */
function sa_home_products_html() {
	$found = sa_home_catalog_products();
	ob_start();
	echo '<div id="sa-home-products" class="sa-catalog__products" data-found="' . esc_attr( (string) $found['total'] ) . '">';
	if ( empty( $found['products'] ) ) {
		echo '<p class="sa-catalog__empty">Không có sản phẩm phù hợp.</p>';
	} else {
		foreach ( $found['products'] as $product ) {
			if ( $product instanceof WC_Product && function_exists( 'sa_render_featured_product_card' ) ) {
				echo sa_render_featured_product_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}
	echo '</div>';
	return (string) ob_get_clean();
}

/**
 * Live counts keyed by checkbox name, including zeros.
 *
 * @return array<string,array<string,int>>
 */
function sa_home_filter_counts() {
	$counts = array();
	foreach ( sa_filter_groups() as $slug => $group ) {
		if ( 'price' === ( $group['type'] ?? '' ) ) {
			continue;
		}
		$map  = sa_filter_term_counts( $slug );
		$name = 'filter_' . $slug;
		$counts[ $name ] = array();
		foreach ( sa_filter_terms_for( $slug, $group ) as $term ) {
			$counts[ $name ][ $term->slug ] = (int) ( $map[ $term->slug ] ?? 0 );
		}
	}
	return $counts;
}

/**
 * Vertical catalog: sticky filter beside the existing category grid and product cards.
 */
function sa_home_catalog_shortcode() {
	if ( ! function_exists( 'sa_child_ux_product_categories_safe' ) ) {
		return '';
	}
	$active  = sa_filter_active_count();
	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	$sorts   = array(
		''           => 'Sắp xếp',
		'date'       => 'Mới nhất',
		'price'      => 'Giá tăng dần',
		'price-desc' => 'Giá giảm dần',
		'popularity' => 'Phổ biến',
	);

	ob_start();
	?>
	<div class="sa-catalog">
		<div class="sa-catalog__bar">
			<button type="button" class="sa-catalog__open" data-sa-open>Bộ lọc<?php echo $active ? ' (' . esc_html( (string) $active ) . ')' : ''; ?></button>
			<label class="sa-catalog__sort">
				<span class="screen-reader-text">Sắp xếp</span>
				<select data-sa-orderby>
					<?php foreach ( $sorts as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $orderby, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<div class="sa-catalog__layout">
			<div class="sa-catalog__intro">
				<div class="sa-section-head is-center">
					<p class="sa-eyebrow">Sản phẩm</p>
					<h2 class="sa-title">DANH MỤC SẢN PHẨM</h2>
					<p class="sa-lead">Giải pháp nguồn điện phù hợp cho từng nhu cầu và hệ thống.</p>
				</div>
			</div>
			<aside class="sa-catalog__side" id="sa-home-sidebar">
				<?php echo sa_filter_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</aside>
			<div class="sa-catalog__main">
				<section class="sa-categories">
					<?php
					$sa_home_cat_ids   = array();
					$sa_home_cat_slugs = array(
						'ups-offline',
						'ups-online',
						'ups-online-rack',
						'ups-cong-nghiep',
						'bien-tan',
						'pin-ac-quy',
					);
					foreach ( $sa_home_cat_slugs as $sa_home_cat_slug ) {
						$sa_home_cat = get_term_by( 'slug', $sa_home_cat_slug, 'product_cat' );
						if ( $sa_home_cat && ! is_wp_error( $sa_home_cat ) ) {
							$sa_home_cat_ids[] = (int) $sa_home_cat->term_id;
						}
					}
					if ( count( $sa_home_cat_ids ) < 6 ) {
						$sa_home_cat_ids = array( 17, 18, 19, 20, 21, 22 );
					}
					echo sa_child_ux_product_categories_safe( array( 'ids' => implode( ',', $sa_home_cat_ids ), 'hide_empty' => '0' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</section>
				<section class="sa-home-products">
					<div class="sa-section-head is-center">
						<h2 class="sa-title">SẢN PHẨM</h2>
					</div>
					<div id="sa-home-chips" class="sa-filter-chips"><?php echo sa_filter_chips_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php echo sa_home_products_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</section>
			</div>
		</div>
		<button type="button" class="sa-filter-backdrop" hidden aria-label="Đóng bộ lọc"></button>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_home_catalog', 'sa_home_catalog_shortcode' );
add_shortcode( 'sa_home_finder', 'sa_home_catalog_shortcode' );

/**
 * Keep filter query args on the static front page instead of the blog index.
 *
 * @param array<string,mixed> $vars Request vars.
 * @return array<string,mixed>
 */
function sa_home_filter_request( $vars ) {
	if ( is_admin() || 'page' !== get_option( 'show_on_front' ) ) {
		return $vars;
	}
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front ) {
		return $vars;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '/';
	if ( '' !== trim( $path, '/' ) ) {
		return $vars;
	}
	$has_filter = ! empty( $_GET['sa_home_filter'] ) || ! empty( $_GET['min_price'] ) || ! empty( $_GET['max_price'] );
	if ( ! $has_filter ) {
		foreach ( array_keys( $_GET ) as $key ) {
			if ( 0 === strpos( (string) $key, 'filter_' ) ) {
				$has_filter = true;
				break;
			}
		}
	}
	if ( $has_filter ) {
		$vars['page_id'] = $front;
	}
	return $vars;
}
add_filter( 'request', 'sa_home_filter_request' );

/**
 * Homepage filter fragment. Updates the product grid only.
 */
function sa_home_filter_fragment() {
	if ( empty( $_GET['sa_home_filter'] ) ) {
		return;
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! is_front_page() && (int) get_queried_object_id() !== $front_id ) {
		return;
	}
	$found = sa_home_catalog_products();
	nocache_headers();
	header( 'X-Robots-Tag: noindex', true );
	wp_send_json_success(
		array(
			'products' => sa_home_products_html(),
			'chips'    => sa_filter_chips_html(),
			'found'    => (int) $found['total'],
			'counts'   => sa_home_filter_counts(),
			'active'   => sa_filter_active_count(),
		)
	);
}
add_action( 'template_redirect', 'sa_home_filter_fragment', 0 );
