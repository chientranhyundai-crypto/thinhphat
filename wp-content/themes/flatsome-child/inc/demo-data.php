<?php
/**
 * Phase 11 — Demo content helpers, homepage shortcodes, REMOVE_DEMO_DATA.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mark any post/attachment as demo.
 *
 * @param int $post_id Post ID.
 */
function sa_mark_demo( $post_id ) {
	update_post_meta( (int) $post_id, '_sa_demo_data', '1' );
}

/**
 * @param int $post_id Post ID.
 * @return bool
 */
function sa_is_demo( $post_id ) {
	return '1' === (string) get_post_meta( (int) $post_id, '_sa_demo_data', true );
}

/**
 * Register demo project CPT.
 */
function sa_register_project_cpt() {
	register_post_type(
		'sa_project',
		array(
			'labels'       => array(
				'name'          => 'Dự án',
				'singular_name' => 'Dự án',
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_menu' => false,
			'rewrite'      => array( 'slug' => 'du-an-item' ),
			'menu_icon'    => 'dashicons-building',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest' => true,
		)
	);
}
add_action( 'init', 'sa_register_project_cpt' );

/**
 * Demo partners (option).
 *
 * @return array<int,array<string,string>>
 */
function sa_get_demo_partners() {
	$stored = get_option( 'sa_demo_partners', array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Demo numbers (option).
 *
 * @return array<int,array<string,string>>
 */
function sa_get_demo_numbers() {
	$stored = get_option( 'sa_demo_numbers', array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Demo videos (option).
 *
 * @return array<int,array<string,string>>
 */
function sa_get_demo_videos() {
	$stored = get_option( 'sa_demo_videos', array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Homepage projects grid.
 *
 * @return string
 */
function sa_home_projects_shortcode() {
	$q = new WP_Query(
		array(
			'post_type'      => 'sa_project',
			'posts_per_page' => 6,
			'post_status'    => 'publish',
			'meta_key'       => '_sa_demo_data',
			'meta_value'     => '1',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $q->have_posts() ) {
		return '<div class="sa-coming"><p class="sa-coming__text">Nội dung dự án sẽ hiển thị khi sẵn sàng.</p></div>';
	}

	$items = $q->posts;
	$html  = '<div class="sa-projects__grid" data-demo="1">';
	foreach ( $items as $i => $post ) {
		$large   = 0 === $i ? ' sa-project--large' : '';
		$url     = get_permalink( $post );
		$thumb   = get_the_post_thumbnail(
			$post,
			0 === $i ? 'large' : 'medium_large',
			array(
				'loading' => 'lazy',
				'alt'     => esc_attr( get_the_title( $post ) ),
			)
		);
		$tag     = get_post_meta( $post->ID, '_sa_project_tag', true );
		$excerpt = get_the_excerpt( $post );
		$html   .= '<a class="sa-project' . esc_attr( $large ) . '" href="' . esc_url( $url ) . '">';
		$html   .= '<div class="sa-project__media' . ( 0 === $i ? ' sa-project__media--main' : '' ) . '">' . $thumb . '</div>';
		$html   .= '<div class="sa-project__body">';
		if ( $tag ) {
			$html .= '<span class="sa-project__tag">' . esc_html( $tag ) . '</span>';
		}
		$html .= '<h3 class="sa-project__title">' . esc_html( get_the_title( $post ) ) . '</h3>';
		if ( 0 === $i && $excerpt ) {
			$html .= '<p class="sa-project__text">' . esc_html( wp_strip_all_tags( $excerpt ) ) . '</p>';
		}
		$html .= '</div></a>';
	}
	$html .= '</div>';
	wp_reset_postdata();
	return $html;
}
add_shortcode( 'sa_home_projects', 'sa_home_projects_shortcode' );

/**
 * Homepage news grid.
 *
 * @return string
 */
function sa_home_news_shortcode() {
	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_sa_demo_data',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_sa_demo_data',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		)
	);

	if ( ! $q->have_posts() ) {
		$q = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'post_status'    => 'publish',
				'meta_key'       => '_sa_demo_data',
				'meta_value'     => '1',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
	}

	if ( ! $q->have_posts() ) {
		return '<div class="sa-coming"><p class="sa-coming__text">Chưa có bài viết công bố.</p></div>';
	}

	$html = '<div class="sa-news__grid">';
	$i    = 0;
	while ( $q->have_posts() ) {
		$q->the_post();
		$feat = 0 === $i ? ' sa-news-card--featured' : '';
		$html .= '<a class="sa-news-card' . esc_attr( $feat ) . '" href="' . esc_url( get_permalink() ) . '">';
		$html .= '<div class="sa-news-card__media">' . get_the_post_thumbnail(
			get_the_ID(),
			0 === $i ? 'large' : 'medium',
			array(
				'loading' => 'lazy',
				'alt'     => esc_attr( get_the_title() ),
			)
		) . '</div>';
		$html .= '<div class="sa-news-card__body">';
		if ( 0 === $i ) {
			$html .= '<span class="sa-news-card__meta">Nổi bật</span>';
		}
		$html .= '<h3 class="sa-news-card__title">' . esc_html( get_the_title() ) . '</h3>';
		if ( 0 === $i ) {
			$html .= '<p class="sa-news-card__excerpt">' . esc_html( wp_strip_all_tags( get_the_excerpt() ) ) . '</p>';
		}
		$html .= '</div></a>';
		++$i;
	}
	$html .= '</div>';
	wp_reset_postdata();
	return $html;
}
add_shortcode( 'sa_home_news', 'sa_home_news_shortcode' );

/**
 * Homepage video cards (thumbnail + play UI, no fake YouTube URLs).
 *
 * @return string
 */
function sa_home_videos_shortcode() {
	$videos = sa_get_demo_videos();
	if ( empty( $videos ) ) {
		return '<div class="sa-coming"><p class="sa-coming__text">Chưa có video công bố.</p></div>';
	}

	$html = '<div class="sa-videos__grid" data-demo="1">';
	foreach ( $videos as $v ) {
		$title = isset( $v['title'] ) ? $v['title'] : '';
		$img   = isset( $v['image_url'] ) ? $v['image_url'] : '';
		$html .= '<div class="sa-video-card">';
		$html .= '<div class="sa-video-card__thumb">';
		if ( $img ) {
			$html .= '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async" />';
		}
		$html .= '<span class="sa-video-card__play" aria-hidden="true"></span></div>';
		$html .= '<h3 class="sa-video-card__title">' . esc_html( $title ) . '</h3>';
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}
add_shortcode( 'sa_home_videos', 'sa_home_videos_shortcode' );

/**
 * Homepage partners (text monograms).
 *
 * @return string
 */
function sa_home_partners_shortcode() {
	$partners = sa_get_demo_partners();
	if ( empty( $partners ) ) {
		return '';
	}

	$html = '<div class="sa-partners__track" data-demo="1" aria-label="Đối tác minh họa">';
	foreach ( $partners as $p ) {
		$mono = isset( $p['mono'] ) ? $p['mono'] : '';
		$name = isset( $p['name'] ) ? $p['name'] : '';
		$html .= '<div class="sa-partners__item sa-partners__item--mono" title="' . esc_attr( $name ) . '">';
		$html .= '<span class="sa-partners__mono">' . esc_html( $mono ) . '</span>';
		$html .= '<span class="sa-partners__name">' . esc_html( $name ) . '</span>';
		$html .= '</div>';
	}
	$html .= '</div>';
	$html .= '<p class="sa-demo-note">Minh họa đối tác (DEMO) — không phải khách hàng thật.</p>';
	return $html;
}
add_shortcode( 'sa_home_partners', 'sa_home_partners_shortcode' );

/**
 * Homepage numbers (demo).
 *
 * @return string
 */
function sa_home_numbers_shortcode() {
	$numbers = sa_get_demo_numbers();
	if ( empty( $numbers ) ) {
		return '';
	}

	$html = '<div class="sa-numbers__grid" data-demo="1">';
	foreach ( $numbers as $n ) {
		$val = isset( $n['value'] ) ? $n['value'] : '';
		$lab = isset( $n['label'] ) ? $n['label'] : '';
		$html .= '<div class="sa-number-card">';
		$html .= '<span class="sa-number-card__value">' . esc_html( $val ) . '</span>';
		$html .= '<span class="sa-number-card__label">' . esc_html( $lab ) . '</span>';
		$html .= '</div>';
	}
	$html .= '</div>';
	$html .= '<p class="sa-demo-note sa-demo-note--light">Số liệu minh họa (DEMO) — không phải số liệu vận hành thật của THỊNH PHÁT.</p>';
	return $html;
}
add_shortcode( 'sa_home_numbers', 'sa_home_numbers_shortcode' );

/**
 * REMOVE_DEMO_DATA — delete only items with _sa_demo_data=1.
 *
 * @return array<string,int>
 */
function sa_remove_demo_data() {
	$stats = array(
		'products'    => 0,
		'projects'    => 0,
		'posts'       => 0,
		'attachments' => 0,
		'options'     => 0,
	);

	$types = array( 'product', 'sa_project', 'du_an', 'post', 'attachment' );
	foreach ( $types as $type ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_sa_demo_data',
				'meta_value'     => '1',
			)
		);
		foreach ( $ids as $id ) {
			// Never delete non-demo even if queried wrong.
			if ( ! sa_is_demo( $id ) ) {
				continue;
			}
			wp_delete_post( (int) $id, true );
			if ( 'product' === $type ) {
				++$stats['products'];
			} elseif ( 'sa_project' === $type || 'du_an' === $type ) {
				++$stats['projects'];
			} elseif ( 'post' === $type ) {
				++$stats['posts'];
			} else {
				++$stats['attachments'];
			}
		}
	}

	foreach ( array( 'sa_demo_partners', 'sa_demo_numbers', 'sa_demo_videos', 'sa_demo_seeded' ) as $opt ) {
		if ( false !== get_option( $opt, false ) ) {
			delete_option( $opt );
			++$stats['options'];
		}
	}

	// Clear demo attribute terms created only for demo? Keep attributes schema.
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
	clean_term_cache( array(), 'product_cat' );

	return $stats;
}

/**
 * WP-CLI: wp sa remove-demo | wp sa seed-demo
 */
function sa_register_cli_commands() {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WP_CLI' ) ) {
		return;
	}

	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	WP_CLI::add_command(
		'sa remove-demo',
		function () {
			$stats = sa_remove_demo_data();
			WP_CLI::success( 'REMOVE_DEMO_DATA done: ' . wp_json_encode( $stats ) );
		}
	);

	WP_CLI::add_command(
		'sa seed-demo',
		function () {
			require_once get_stylesheet_directory() . '/inc/demo-seed.php';
			$result = sa_seed_demo_data( true );
			WP_CLI::success( 'SEED_DEMO done: ' . wp_json_encode( $result ) );
		}
	);
}
add_action( 'cli_init', 'sa_register_cli_commands' );
sa_register_cli_commands();

/**
 * Single product: CTA Nhận tư vấn.
 */
function sa_single_product_consult_cta() {
	$url = sa_product_consult_url();
	echo '<p class="sa-single-consult"><a class="sa-btn sa-btn--primary" href="' . esc_url( $url ) . '">NHẬN TƯ VẤN</a></p>';
}
add_action( 'woocommerce_single_product_summary', 'sa_single_product_consult_cta', 35 );

/** Product consult button CSS lives in style.css. */

