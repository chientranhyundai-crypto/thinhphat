<?php
/**
 * Projects — CPT du_an, listing page /du-an/, and single template.
 * Cards are queried. Demo rows are seeded once and marked _sa_demo_data=1.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Solution groups used by the public filter.
 *
 * @return array<string,string>
 */
function sa_du_an_groups() {
	return array(
		'may-phat-dien' => 'Máy phát điện',
		'ups'           => 'UPS',
		'cong-nghiep'   => 'Công nghiệp',
		'doanh-nghiep'  => 'Doanh nghiệp',
	);
}

/**
 * Register the project type and its groups.
 */
function sa_du_an_register() {
	register_post_type(
		'du_an',
		array(
			'labels'              => array(
				'name'               => 'Dự án',
				'singular_name'      => 'Dự án',
				'add_new'            => 'Thêm mới',
				'add_new_item'       => 'Thêm dự án',
				'edit_item'          => 'Sửa dự án',
				'new_item'           => 'Dự án mới',
				'view_item'          => 'Xem dự án',
				'search_items'       => 'Tìm dự án',
				'not_found'          => 'Chưa có dự án',
				'not_found_in_trash' => 'Không có dự án trong thùng rác',
				'all_items'          => 'Tất cả dự án',
				'menu_name'          => 'Dự án',
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-portfolio',
			'menu_position'       => 21,
			'rewrite'             => array(
				'slug'       => 'du-an',
				'with_front' => false,
			),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'capability_type'     => 'post',
		)
	);

	register_taxonomy(
		'du_an_nhom',
		'du_an',
		array(
			'labels'            => array(
				'name'          => 'Nhóm dự án',
				'singular_name' => 'Nhóm dự án',
				'add_new_item'  => 'Thêm nhóm',
				'edit_item'     => 'Sửa nhóm',
				'search_items'  => 'Tìm nhóm',
				'all_items'     => 'Các nhóm',
				'menu_name'     => 'Nhóm',
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'sa_du_an_register' );

/**
 * Keep /du-an/{slug}/ ahead of the listing page.
 */
function sa_du_an_rewrite() {
	add_rewrite_rule( '^du-an/([^/]+)/?$', 'index.php?post_type=du_an&name=$matches[1]', 'top' );
}
add_action( 'init', 'sa_du_an_rewrite', 20 );

/**
 * Flush once after the rules exist.
 */
function sa_du_an_flush_once() {
	if ( '1' === get_option( 'sa_du_an_rewrite' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'sa_du_an_rewrite', '1' );
}
add_action( 'init', 'sa_du_an_flush_once', 99 );

/**
 * Ensure the four filter groups exist.
 */
function sa_du_an_ensure_terms() {
	if ( ! taxonomy_exists( 'du_an_nhom' ) ) {
		return;
	}
	foreach ( sa_du_an_groups() as $slug => $name ) {
		if ( ! term_exists( $slug, 'du_an_nhom' ) ) {
			wp_insert_term( $name, 'du_an_nhom', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'sa_du_an_ensure_terms', 30 );

/**
 * @param int $post_id Project ID.
 * @return string[]
 */
function sa_du_an_term_slugs( $post_id ) {
	$terms = get_the_terms( $post_id, 'du_an_nhom' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}
	return array_values( wp_list_pluck( $terms, 'slug' ) );
}

/**
 * @param int $post_id Project ID.
 * @return string
 */
function sa_du_an_term_label( $post_id ) {
	$terms = get_the_terms( $post_id, 'du_an_nhom' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return implode( ' · ', wp_list_pluck( $terms, 'name' ) );
}

/**
 * Spec rows stored as "Nhãn | Giá trị" per line.
 *
 * @param int $post_id Project ID.
 * @return array<int,array{label:string,value:string}>
 */
function sa_du_an_spec_rows( $post_id ) {
	$raw  = (string) get_post_meta( $post_id, '_sa_du_an_specs', true );
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts  = array_map( 'trim', explode( '|', $line, 2 ) );
		$rows[] = array(
			'label' => $parts[0],
			'value' => isset( $parts[1] ) ? $parts[1] : '',
		);
	}
	return $rows;
}

/**
 * @param int $post_id Project ID.
 * @return int[]
 */
function sa_du_an_gallery_ids( $post_id ) {
	$raw = (string) get_post_meta( $post_id, '_sa_du_an_gallery', true );
	$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
	return array_values( array_unique( $ids ) );
}

/**
 * @param int    $post_id Project ID.
 * @param string $size    Image size.
 * @param string $class   CSS class.
 * @return string
 */
function sa_du_an_image( $post_id, $size, $class ) {
	$alt = get_the_title( $post_id );
	if ( sa_is_demo( $post_id ) ) {
		$alt .= ' — ảnh minh họa DEMO';
	}
	$html = get_the_post_thumbnail(
		$post_id,
		$size,
		array(
			'class'    => $class,
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
	return $html ? $html : '';
}

/**
 * Published projects, featured first.
 *
 * @return WP_Post[]
 */
function sa_du_an_posts() {
	$q = new WP_Query(
		array(
			'post_type'      => 'du_an',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$posts = $q->posts;
	wp_reset_postdata();
	return $posts;
}

/**
 * Listing page.
 *
 * @return string
 */
function sa_du_an_page_shortcode() {
	$posts    = sa_du_an_posts();
	$featured = null;
	$grid     = array();
	foreach ( $posts as $post ) {
		if ( ! $featured && '1' === (string) get_post_meta( $post->ID, '_sa_du_an_featured', true ) ) {
			$featured = $post;
			continue;
		}
		$grid[] = $post;
	}
	if ( ! $featured && $posts ) {
		$featured = $posts[0];
		$grid     = array_slice( $posts, 1 );
	}

	$contact = home_url( '/lien-he/' );
	$hotline = sa_business( 'hotline' );
	$tel     = sa_business( 'hotline_tel' );
	$hero_id = 51;

	ob_start();
	?>
	<article class="sa-da">
		<section class="sa-da-hero" aria-labelledby="sa-da-title">
			<div class="sa-da-hero__inner">
				<div class="sa-da-hero__copy">
					<p class="sa-da-eyebrow">Dự án Thịnh Phát</p>
					<h1 id="sa-da-title">Giải pháp nguồn điện trong thực tế</h1>
					<p class="sa-da-lead">Các mô hình dưới đây cho thấy cách Thịnh Phát trình bày một bài toán nguồn điện: nhu cầu vận hành, thiết bị, công suất và khu vực. Nội dung đang hiển thị là dữ liệu DEMO để hoàn thiện giao diện, không phải công trình đã bàn giao.</p>
				</div>
				<figure class="sa-da-hero__media">
					<?php
					echo wp_get_attachment_image(
						$hero_id,
						'large',
						false,
						array(
							'class'    => 'sa-da-hero__img',
							'alt'      => 'Phòng máy và hệ thống nguồn điện công nghiệp',
							'loading'  => 'eager',
							'decoding' => 'async',
							'sizes'    => '(max-width: 900px) 100vw, 48vw',
						)
					);
					?>
				</figure>
			</div>
		</section>

		<section class="sa-da-board" aria-labelledby="sa-da-board-title">
			<div class="sa-da-wrap">
				<div class="sa-da-filters" role="toolbar" aria-label="Lọc dự án">
					<button type="button" class="is-active" data-sa-da-filter="all" aria-pressed="true">Tất cả</button>
					<?php foreach ( sa_du_an_groups() as $slug => $label ) : ?>
						<button type="button" data-sa-da-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</div>

				<?php if ( ! $posts ) : ?>
					<p class="sa-da-empty">Chưa có dự án. Thêm mới trong mục Dự án của trang quản trị.</p>
				<?php else : ?>
					<?php if ( $featured ) : ?>
						<?php echo sa_du_an_featured_html( $featured ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>

					<h2 id="sa-da-board-title" class="sa-da-board__title">Dự án</h2>
					<div class="sa-da-grid">
						<?php foreach ( $grid as $post ) : ?>
							<?php echo sa_du_an_card_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
					<p class="sa-da-empty" data-sa-da-empty hidden>Chưa có dự án trong nhóm này.</p>
				<?php endif; ?>
			</div>
		</section>

		<?php echo sa_du_an_cta_html( $contact, $hotline, $tel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_du_an_page', 'sa_du_an_page_shortcode' );

/**
 * @param WP_Post $post Project.
 * @return string
 */
function sa_du_an_featured_html( $post ) {
	$id    = $post->ID;
	$slugs = sa_du_an_term_slugs( $id );
	$demo  = sa_is_demo( $id );
	ob_start();
	?>
	<article class="sa-da-feature" data-sa-da-item data-sa-da-terms="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
		<a class="sa-da-feature__media" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php echo sa_du_an_image( $id, 'large', 'sa-da-feature__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $demo ) : ?><span class="sa-da-badge">DEMO</span><?php endif; ?>
		</a>
		<div class="sa-da-feature__body">
			<p class="sa-da-kicker">Dự án nổi bật</p>
			<h2 class="sa-da-feature__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h2>
			<dl class="sa-da-facts">
				<?php echo sa_du_an_facts_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</dl>
			<?php if ( $post->post_excerpt ) : ?>
				<p class="sa-da-text"><?php echo esc_html( $post->post_excerpt ); ?></p>
			<?php endif; ?>
			<a class="sa-da-more" href="<?php echo esc_url( get_permalink( $post ) ); ?>">Xem dự án <span aria-hidden="true">→</span></a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param WP_Post $post Project.
 * @return string
 */
function sa_du_an_card_html( $post ) {
	$id    = $post->ID;
	$slugs = sa_du_an_term_slugs( $id );
	$demo  = sa_is_demo( $id );
	ob_start();
	?>
	<article class="sa-da-card" data-sa-da-item data-sa-da-terms="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
		<a class="sa-da-card__media" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php echo sa_du_an_image( $id, 'medium_large', 'sa-da-card__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="sa-da-card__arrow" aria-hidden="true">→</span>
			<?php if ( $demo ) : ?><span class="sa-da-badge">DEMO</span><?php endif; ?>
		</a>
		<div class="sa-da-card__body">
			<?php if ( sa_du_an_term_label( $id ) ) : ?>
				<p class="sa-da-cat"><?php echo esc_html( sa_du_an_term_label( $id ) ); ?></p>
			<?php endif; ?>
			<h3 class="sa-da-card__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
			<dl class="sa-da-facts sa-da-facts--compact">
				<?php echo sa_du_an_facts_html( $id, false, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</dl>
			<?php if ( $post->post_excerpt ) : ?>
				<p class="sa-da-text"><?php echo esc_html( $post->post_excerpt ); ?></p>
			<?php endif; ?>
			<a class="sa-da-more" href="<?php echo esc_url( get_permalink( $post ) ); ?>">Xem chi tiết <span aria-hidden="true">→</span></a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param int  $post_id Project ID.
 * @param bool $region  Include region.
 * @return string
 */
function sa_du_an_facts_html( $post_id, $region = true, $solution = true ) {
	$rows = array();
	if ( $solution ) {
		$rows['Loại giải pháp'] = sa_du_an_term_label( $post_id );
	}
	$rows['Thiết bị']  = (string) get_post_meta( $post_id, '_sa_du_an_equipment', true );
	$rows['Công suất'] = (string) get_post_meta( $post_id, '_sa_du_an_capacity', true );
	if ( $region ) {
		$rows['Khu vực'] = (string) get_post_meta( $post_id, '_sa_du_an_region', true );
	}
	$html = '';
	foreach ( $rows as $label => $value ) {
		if ( '' === trim( $value ) ) {
			continue;
		}
		$html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
	}
	return $html;
}

/**
 * @param string $contact Contact URL.
 * @param string $hotline Display hotline.
 * @param string $tel     Dial string.
 * @return string
 */
function sa_du_an_cta_html( $contact, $hotline, $tel ) {
	ob_start();
	?>
	<section class="sa-da-cta" aria-labelledby="sa-da-cta-title">
		<div class="sa-da-wrap sa-da-cta__inner">
			<h2 id="sa-da-cta-title">Bạn đang cần giải pháp nguồn điện cho dự án của mình?</h2>
			<div class="sa-da-cta__actions">
				<a class="sa-da-btn" href="<?php echo esc_url( $contact ); ?>">NHẬN TƯ VẤN →</a>
				<?php if ( $hotline && $tel ) : ?>
					<p class="sa-da-cta__phone">Hotline: <a href="<?php echo esc_url( 'tel:' . preg_replace( '/\D+/', '', $tel ) ); ?>"><?php echo esc_html( $hotline ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Single project layout.
 *
 * @param int $post_id Project ID.
 * @return string
 */
function sa_du_an_render_single( $post_id ) {
	$post    = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	$contact = home_url( '/lien-he/' );
	$hotline = sa_business( 'hotline' );
	$tel     = sa_business( 'hotline_tel' );
	$need    = (string) get_post_meta( $post_id, '_sa_du_an_need', true );
	$solve   = (string) get_post_meta( $post_id, '_sa_du_an_solution', true );
	$result  = (string) get_post_meta( $post_id, '_sa_du_an_result', true );
	$equip   = (string) get_post_meta( $post_id, '_sa_du_an_equipment', true );
	$specs   = sa_du_an_spec_rows( $post_id );
	$gallery = sa_du_an_gallery_ids( $post_id );
	$related = sa_du_an_related( $post_id );

	ob_start();
	?>
	<article class="sa-da sa-da-single">
		<section class="sa-da-cover" aria-labelledby="sa-da-single-title">
			<div class="sa-da-cover__media">
				<?php echo sa_du_an_image( $post_id, 'large', 'sa-da-cover__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="sa-da-wrap sa-da-cover__copy">
				<p class="sa-da-eyebrow"><a href="<?php echo esc_url( home_url( '/du-an/' ) ); ?>">Dự án Thịnh Phát</a></p>
				<?php if ( sa_is_demo( $post_id ) ) : ?><p class="sa-da-badge sa-da-badge--inline">DEMO</p><?php endif; ?>
				<?php if ( sa_du_an_term_label( $post_id ) ) : ?>
					<p class="sa-da-cat"><?php echo esc_html( sa_du_an_term_label( $post_id ) ); ?></p>
				<?php endif; ?>
				<h1 id="sa-da-single-title"><?php echo esc_html( get_the_title( $post ) ); ?></h1>
				<dl class="sa-da-facts">
					<?php echo sa_du_an_facts_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</dl>
			</div>
		</section>

		<?php if ( trim( $post->post_content ) ) : ?>
			<section class="sa-da-block" aria-labelledby="sa-da-overview">
				<div class="sa-da-wrap sa-da-prose">
					<h2 id="sa-da-overview">Tổng quan</h2>
					<?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $need ) : ?>
			<section class="sa-da-block sa-da-block--alt" aria-labelledby="sa-da-need">
				<div class="sa-da-wrap sa-da-prose">
					<h2 id="sa-da-need">Nhu cầu</h2>
					<?php echo wpautop( esc_html( $need ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $solve ) : ?>
			<section class="sa-da-block" aria-labelledby="sa-da-solve">
				<div class="sa-da-wrap sa-da-prose">
					<h2 id="sa-da-solve">Giải pháp</h2>
					<?php echo wpautop( esc_html( $solve ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $equip || $specs ) : ?>
			<section class="sa-da-block sa-da-block--alt" aria-labelledby="sa-da-specs">
				<div class="sa-da-wrap">
					<h2 id="sa-da-specs">Thiết bị và thông số</h2>
					<?php if ( $equip ) : ?>
						<p class="sa-da-text"><?php echo esc_html( $equip ); ?></p>
					<?php endif; ?>
					<?php if ( $specs ) : ?>
						<div class="sa-da-table-wrap">
							<table class="sa-da-table">
								<tbody>
									<?php foreach ( $specs as $row ) : ?>
										<tr>
											<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
											<td><?php echo esc_html( $row['value'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $gallery ) : ?>
			<section class="sa-da-block" aria-labelledby="sa-da-gallery">
				<div class="sa-da-wrap">
					<h2 id="sa-da-gallery">Gallery</h2>
					<ul class="sa-da-gallery">
						<?php foreach ( $gallery as $gid ) : ?>
							<li>
								<?php
								echo wp_get_attachment_image(
									$gid,
									'medium_large',
									false,
									array(
										'alt'      => sa_is_demo( $post_id ) ? 'Ảnh minh họa DEMO' : '',
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $result ) : ?>
			<section class="sa-da-block sa-da-block--alt" aria-labelledby="sa-da-result">
				<div class="sa-da-wrap sa-da-prose">
					<h2 id="sa-da-result">Kết quả</h2>
					<?php echo wpautop( esc_html( $result ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $related ) : ?>
			<section class="sa-da-block" aria-labelledby="sa-da-related">
				<div class="sa-da-wrap">
					<h2 id="sa-da-related">Dự án liên quan</h2>
					<div class="sa-da-grid">
						<?php foreach ( $related as $item ) : ?>
							<?php echo sa_du_an_card_html( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php echo sa_du_an_cta_html( $contact, $hotline, $tel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Up to three projects that share a group.
 *
 * @param int $post_id Current project.
 * @return WP_Post[]
 */
function sa_du_an_related( $post_id ) {
	$slugs = sa_du_an_term_slugs( $post_id );
	$args  = array(
		'post_type'      => 'du_an',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'post__not_in'   => array( $post_id ),
		'no_found_rows'  => true,
	);
	if ( $slugs ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'du_an_nhom',
				'field'    => 'slug',
				'terms'    => $slugs,
			),
		);
	}
	$q = new WP_Query( $args );
	$posts = $q->posts;
	wp_reset_postdata();
	return $posts;
}

/**
 * @param bool $is_page Listing or single.
 * @return bool
 */
function sa_du_an_is_view() {
	return is_page( 'du-an' ) || is_singular( 'du_an' );
}

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function sa_du_an_body_class( $classes ) {
	if ( sa_du_an_is_view() ) {
		$classes[] = 'sa-du-an';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_du_an_body_class' );

/**
 * Assets for the listing and the detail template only.
 */
function sa_du_an_assets() {
	if ( ! sa_du_an_is_view() ) {
		return;
	}
	if ( is_page( 'du-an' ) ) {
		$js = get_stylesheet_directory() . '/assets/js/projects.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'sa-projects', get_stylesheet_directory_uri() . '/assets/js/projects.js', array(), (string) filemtime( $js ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'sa_du_an_assets', 25 );

/**
 * @param array $parts Title parts.
 * @return array
 */
function sa_du_an_document_title( $parts ) {
	if ( is_page( 'du-an' ) ) {
		$parts['title'] = 'Dự án — Giải pháp nguồn điện';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sa_du_an_document_title' );

/**
 * Meta box.
 */
function sa_du_an_metabox() {
	add_meta_box( 'sa_du_an_fields', 'Thông tin dự án', 'sa_du_an_metabox_html', 'du_an', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'sa_du_an_metabox' );

/**
 * @param WP_Post $post Project.
 */
function sa_du_an_metabox_html( $post ) {
	wp_nonce_field( 'sa_du_an_save', 'sa_du_an_nonce' );
	$fields = array(
		'equipment' => (string) get_post_meta( $post->ID, '_sa_du_an_equipment', true ),
		'capacity'  => (string) get_post_meta( $post->ID, '_sa_du_an_capacity', true ),
		'region'    => (string) get_post_meta( $post->ID, '_sa_du_an_region', true ),
		'need'      => (string) get_post_meta( $post->ID, '_sa_du_an_need', true ),
		'solution'  => (string) get_post_meta( $post->ID, '_sa_du_an_solution', true ),
		'result'    => (string) get_post_meta( $post->ID, '_sa_du_an_result', true ),
		'specs'     => (string) get_post_meta( $post->ID, '_sa_du_an_specs', true ),
	);
	$featured = '1' === (string) get_post_meta( $post->ID, '_sa_du_an_featured', true );
	$gallery  = sa_du_an_gallery_ids( $post->ID );
	?>
	<p><label for="sa_du_an_excerpt"><strong>Mô tả ngắn</strong></label><br>
		<textarea id="sa_du_an_excerpt" name="sa_du_an_excerpt" rows="3" class="large-text"><?php echo esc_textarea( $post->post_excerpt ); ?></textarea></p>
	<p><label for="sa_du_an_equipment"><strong>Thiết bị</strong></label><br>
		<input id="sa_du_an_equipment" name="sa_du_an_equipment" type="text" class="widefat" value="<?php echo esc_attr( $fields['equipment'] ); ?>"></p>
	<p><label for="sa_du_an_capacity"><strong>Công suất</strong></label><br>
		<input id="sa_du_an_capacity" name="sa_du_an_capacity" type="text" class="widefat" value="<?php echo esc_attr( $fields['capacity'] ); ?>"></p>
	<p><label for="sa_du_an_region"><strong>Khu vực</strong></label><br>
		<input id="sa_du_an_region" name="sa_du_an_region" type="text" class="widefat" value="<?php echo esc_attr( $fields['region'] ); ?>"></p>
	<p><label><input type="checkbox" name="sa_du_an_featured" value="1" <?php checked( $featured ); ?>> Dự án nổi bật trên trang /du-an/</label></p>
	<p><label for="sa_du_an_need"><strong>Nhu cầu</strong></label><br>
		<textarea id="sa_du_an_need" name="sa_du_an_need" rows="4" class="large-text"><?php echo esc_textarea( $fields['need'] ); ?></textarea></p>
	<p><label for="sa_du_an_solution"><strong>Giải pháp</strong></label><br>
		<textarea id="sa_du_an_solution" name="sa_du_an_solution" rows="4" class="large-text"><?php echo esc_textarea( $fields['solution'] ); ?></textarea></p>
	<p><label for="sa_du_an_result"><strong>Kết quả</strong></label><br>
		<textarea id="sa_du_an_result" name="sa_du_an_result" rows="4" class="large-text"><?php echo esc_textarea( $fields['result'] ); ?></textarea></p>
	<p><label for="sa_du_an_specs"><strong>Thông số</strong> — mỗi dòng theo dạng Nhãn | Giá trị</label><br>
		<textarea id="sa_du_an_specs" name="sa_du_an_specs" rows="6" class="large-text"><?php echo esc_textarea( $fields['specs'] ); ?></textarea></p>
	<p><strong>Gallery</strong></p>
	<div id="sa-du-an-gallery" class="sa-du-an-gallery">
		<?php foreach ( $gallery as $gid ) : ?>
			<span data-id="<?php echo esc_attr( (string) $gid ); ?>"><?php echo wp_get_attachment_image( $gid, 'thumbnail' ); ?></span>
		<?php endforeach; ?>
	</div>
	<input type="hidden" id="sa_du_an_gallery" name="sa_du_an_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>">
	<p>
		<button type="button" class="button" id="sa-du-an-gallery-add">Chọn ảnh</button>
		<button type="button" class="button" id="sa-du-an-gallery-clear">Xóa gallery</button>
	</p>
	<p class="description">Ảnh đại diện dùng làm ảnh bìa. Gallery hiện ở trang chi tiết. Chọn nhóm ở cột phải. Nội dung soạn thảo là phần Tổng quan.</p>
	<?php
}

/**
 * @param int $post_id Project ID.
 */
function sa_du_an_save( $post_id ) {
	if ( ! isset( $_POST['sa_du_an_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sa_du_an_nonce'] ) ), 'sa_du_an_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$map = array(
		'sa_du_an_equipment' => '_sa_du_an_equipment',
		'sa_du_an_capacity'  => '_sa_du_an_capacity',
		'sa_du_an_region'    => '_sa_du_an_region',
		'sa_du_an_need'      => '_sa_du_an_need',
		'sa_du_an_solution'  => '_sa_du_an_solution',
		'sa_du_an_result'    => '_sa_du_an_result',
		'sa_du_an_specs'     => '_sa_du_an_specs',
		'sa_du_an_gallery'   => '_sa_du_an_gallery',
	);
	foreach ( $map as $field => $meta ) {
		$value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
		if ( 'sa_du_an_gallery' === $field ) {
			$ids   = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
			$value = implode( ',', $ids );
			update_post_meta( $post_id, $meta, $value );
			continue;
		}
		update_post_meta( $post_id, $meta, sanitize_textarea_field( (string) $value ) );
	}
	update_post_meta( $post_id, '_sa_du_an_featured', empty( $_POST['sa_du_an_featured'] ) ? '0' : '1' );

	if ( isset( $_POST['sa_du_an_excerpt'] ) ) {
		remove_action( 'save_post_du_an', 'sa_du_an_save' );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['sa_du_an_excerpt'] ) ),
			)
		);
		add_action( 'save_post_du_an', 'sa_du_an_save' );
	}
}
add_action( 'save_post_du_an', 'sa_du_an_save' );

/**
 * Gallery picker on the project editor.
 *
 * @param string $hook Admin page.
 */
function sa_du_an_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'du_an' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'jquery',
		"(function($){
			function sync(){
				var ids=[];
				$('#sa-du-an-gallery span').each(function(){ ids.push($(this).data('id')); });
				$('#sa_du_an_gallery').val(ids.join(','));
			}
			$('#sa-du-an-gallery-add').on('click', function(e){
				e.preventDefault();
				var frame = wp.media({ title: 'Gallery dự án', button: { text: 'Dùng các ảnh này' }, multiple: true });
				frame.on('select', function(){
					frame.state().get('selection').each(function(file){
						var att = file.toJSON();
						var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
						$('#sa-du-an-gallery').append('<span data-id=\"'+att.id+'\"><img src=\"'+url+'\" alt=\"\"></span>');
					});
					sync();
				});
				frame.open();
			});
			$('#sa-du-an-gallery-clear').on('click', function(e){
				e.preventDefault();
				$('#sa-du-an-gallery').empty();
				sync();
			});
		})(jQuery);"
	);
}
add_action( 'admin_enqueue_scripts', 'sa_du_an_admin_assets' );

/**
 * Seed eight DEMO projects once. Does not mark shared product photos as demo.
 */
function sa_du_an_seed_demo() {
	if ( '1' === get_option( 'sa_du_an_demo_seeded' ) || ! post_type_exists( 'du_an' ) ) {
		return;
	}
	sa_du_an_ensure_terms();

	$note = 'Đây là mô hình minh họa (DEMO) để trình bày cách một hồ sơ dự án được bố cục. Không phải công trình đã triển khai và không gắn với khách hàng nào.';
	$rows = array(
		array(
			'slug'      => 'demo-nguon-du-phong-nha-xuong',
			'title'     => 'DEMO — Mô hình nguồn dự phòng cho nhà xưởng',
			'excerpt'   => 'Mô hình minh họa máy phát dự phòng cho khu sản xuất khi điện lưới gián đoạn. Không phải hồ sơ bàn giao.',
			'groups'    => array( 'may-phat-dien', 'cong-nghiep' ),
			'equipment' => 'Máy phát điện công nghiệp (minh họa)',
			'capacity'  => '160 kVA (minh họa)',
			'region'    => 'Khu công nghiệp (minh họa)',
			'featured'  => '1',
			'image'     => 310,
			'gallery'   => array( 310, 312, 444 ),
			'need'      => 'Một khu xưởng cần nguồn chạy tiếp khi điện lưới mất, đủ cho tải sản xuất và khu điều khiển trong thời gian minh họa.',
			'solution'  => 'Bố trí máy phát công nghiệp làm nguồn dự phòng, tách tải ưu tiên và đối chiếu công suất liên tục với công suất dự phòng của model được chọn.',
			'result'    => 'Hồ sơ DEMO cho thấy đủ các mục để đội ngũ điền khi có dự án thật: nhu cầu, giải pháp, thông số và hình ảnh thiết bị.',
			'specs'     => "Loại giải pháp | Máy phát điện dự phòng\nThiết bị minh họa | Máy phát điện công nghiệp\nCông suất minh họa | 160 kVA\nSố pha | 3 pha\nTính chất | DEMO — không phải công trình đã bàn giao",
		),
		array(
			'slug'      => 'demo-ups-phong-dieu-khien',
			'title'     => 'DEMO — Mô hình UPS cho phòng điều khiển',
			'excerpt'   => 'Mô hình UPS online giữ điện cho tủ điều khiển trong lúc chuyển nguồn. Dữ liệu minh họa.',
			'groups'    => array( 'ups', 'cong-nghiep' ),
			'equipment' => 'Bộ lưu điện online (minh họa)',
			'capacity'  => '20 kVA (minh họa)',
			'region'    => 'Phòng điều khiển (minh họa)',
			'featured'  => '0',
			'image'     => 305,
			'gallery'   => array( 305, 366, 337 ),
			'need'      => 'Tủ điều khiển và màn hình giám sát không được mất điện trong khoảng chuyển từ lưới sang máy phát.',
			'solution'  => 'Đặt UPS online phía trước tải nhạy, tính thời gian lưu điện theo phụ tải thật khi có số liệu.',
			'result'    => 'Mô hình DEMO mô tả vị trí của UPS trong chuỗi nguồn, chưa gắn hiện trường.',
			'specs'     => "Loại giải pháp | UPS online\nThiết bị minh họa | Bộ lưu điện 3 pha\nCông suất minh họa | 20 kVA\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-may-phat-toa-nha',
			'title'     => 'DEMO — Mô hình máy phát cho tòa nhà',
			'excerpt'   => 'Mô hình nguồn dự phòng cho thang máy, chiếu sáng và khu kỹ thuật của tòa nhà. Minh họa giao diện.',
			'groups'    => array( 'may-phat-dien', 'doanh-nghiep' ),
			'equipment' => 'Máy phát điện dự phòng (minh họa)',
			'capacity'  => '85 kVA (minh họa)',
			'region'    => 'Tòa nhà (minh họa)',
			'featured'  => '0',
			'image'     => 141,
			'gallery'   => array( 141, 340, 345 ),
			'need'      => 'Tòa nhà cần nguồn cho tải dùng chung khi mất điện lưới, với danh sách tải được chốt trước khi chọn model.',
			'solution'  => 'Chọn máy phát theo tổng tải ưu tiên và dòng khởi động, không cộng toàn bộ phụ tải cùng một lúc.',
			'result'    => 'Bản DEMO dùng để xem cách trình bày công suất và khu vực trên thẻ dự án.',
			'specs'     => "Loại giải pháp | Máy phát điện\nCông suất minh họa | 85 kVA\nĐối tượng | Tòa nhà (minh họa)\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-ups-van-phong',
			'title'     => 'DEMO — Mô hình UPS cho văn phòng',
			'excerpt'   => 'Mô hình bảo vệ máy trạm, tổng đài và thiết bị mạng văn phòng. Không gắn tên doanh nghiệp.',
			'groups'    => array( 'ups', 'doanh-nghiep' ),
			'equipment' => 'Bộ lưu điện cho khối văn phòng (minh họa)',
			'capacity'  => '6 kVA (minh họa)',
			'region'    => 'Văn phòng (minh họa)',
			'featured'  => '0',
			'image'     => 366,
			'gallery'   => array( 366, 358, 354 ),
			'need'      => 'Khối làm việc cần vài phút để lưu dữ liệu và tắt thiết bị an toàn khi điện lưới mất.',
			'solution'  => 'UPS đặt tại tủ điện tầng hoặc phòng mạng, công suất lấy từ danh sách thiết bị thực tế.',
			'result'    => 'Mô hình DEMO cho nhóm lọc Doanh nghiệp và UPS.',
			'specs'     => "Loại giải pháp | UPS\nCông suất minh họa | 6 kVA\nVị trí | Văn phòng (minh họa)\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-day-chuyen-san-xuat',
			'title'     => 'DEMO — Mô hình nguồn cho dây chuyền',
			'excerpt'   => 'Mô hình máy phát cho dây chuyền cần chạy liên tục trong ca sản xuất. Số liệu là minh họa.',
			'groups'    => array( 'may-phat-dien', 'cong-nghiep' ),
			'equipment' => 'Máy phát điện công nghiệp (minh họa)',
			'capacity'  => '150 kVA (minh họa)',
			'region'    => 'Dây chuyền sản xuất (minh họa)',
			'featured'  => '0',
			'image'     => 444,
			'gallery'   => array( 444, 310, 308 ),
			'need'      => 'Dây chuyền không muốn dừng giữa ca khi lưới mất, nên cần nguồn độc lập đủ cho cụm máy đã liệt kê.',
			'solution'  => 'Tách tải dây chuyền khỏi tải phụ, rồi đối chiếu công suất liên tục của máy phát.',
			'result'    => 'Hồ sơ DEMO, dùng để kiểm tra bố cục thông số trên trang chi tiết.',
			'specs'     => "Loại giải pháp | Máy phát điện công nghiệp\nCông suất minh họa | 150 kVA\nSố pha | 3 pha\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-ups-trung-tam-du-lieu',
			'title'     => 'DEMO — Mô hình UPS cho phòng máy',
			'excerpt'   => 'Mô hình UPS cho tủ thiết bị và thiết bị mạng trong phòng máy. Không phải một trung tâm dữ liệu có thật.',
			'groups'    => array( 'ups', 'doanh-nghiep' ),
			'equipment' => 'Bộ lưu điện online (minh họa)',
			'capacity'  => '10 kVA (minh họa)',
			'region'    => 'Phòng máy (minh họa)',
			'featured'  => '0',
			'image'     => 337,
			'gallery'   => array( 337, 305, 349 ),
			'need'      => 'Thiết bị mạng và máy chủ cần điện không gián đoạn trong lúc chuyển nguồn hoặc tắt có kiểm soát.',
			'solution'  => 'UPS online kèm thời gian lưu điện tính theo tải đo được, không ước lượng khi chưa có số.',
			'result'    => 'Mô hình DEMO để thấy gallery và bảng thông số.',
			'specs'     => "Loại giải pháp | UPS online\nCông suất minh họa | 10 kVA\nVị trí | Phòng máy (minh họa)\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-may-phat-hien-truong',
			'title'     => 'DEMO — Mô hình máy phát hiện trường',
			'excerpt'   => 'Mô hình máy phát di động cho vị trí chưa có điện lưới ổn định. Minh họa, không gắn công trình.',
			'groups'    => array( 'may-phat-dien', 'cong-nghiep' ),
			'equipment' => 'Máy phát điện chạy dầu (minh họa)',
			'capacity'  => '25 kVA (minh họa)',
			'region'    => 'Hiện trường (minh họa)',
			'featured'  => '0',
			'image'     => 451,
			'gallery'   => array( 451, 450, 459 ),
			'need'      => 'Vị trí thi công hoặc vận hành tạm cần nguồn độc lập trong một khoảng thời gian xác định.',
			'solution'  => 'Chọn máy theo tải thiết bị thi công và điều kiện đặt máy: thông gió, nhiên liệu, khoảng cách.',
			'result'    => 'Bản DEMO phục vụ bộ lọc Máy phát điện.',
			'specs'     => "Loại giải pháp | Máy phát điện\nNhiên liệu | Dầu (minh họa)\nCông suất minh họa | 25 kVA\nTính chất | DEMO",
		),
		array(
			'slug'      => 'demo-nguon-khoi-van-phong-nha-may',
			'title'     => 'DEMO — Mô hình nguồn cho khối văn phòng nhà máy',
			'excerpt'   => 'Mô hình kết hợp máy phát và UPS cho khu văn phòng nằm trong nhà máy. Không nêu tên nhà máy.',
			'groups'    => array( 'may-phat-dien', 'ups', 'cong-nghiep', 'doanh-nghiep' ),
			'equipment' => 'Máy phát điện và bộ lưu điện (minh họa)',
			'capacity'  => '40 kVA + 6 kVA UPS (minh họa)',
			'region'    => 'Khối văn phòng trong nhà máy (minh họa)',
			'featured'  => '0',
			'image'     => 312,
			'gallery'   => array( 312, 366, 340 ),
			'need'      => 'Khu văn phòng cần UPS cho thiết bị mạng, trong khi tải lớn hơn chuyển sang máy phát.',
			'solution'  => 'Chia hai lớp: UPS cho tải không được rớt, máy phát cho tải chạy dài sau khi lưới mất.',
			'result'    => 'Mô hình DEMO thuộc cả bốn nhóm lọc, để kiểm tra bộ lọc nhiều danh mục.',
			'specs'     => "Máy phát minh họa | 40 kVA\nUPS minh họa | 6 kVA\nCách chia tải | UPS cho mạng, máy phát cho tải còn lại\nTính chất | DEMO",
		),
	);

	foreach ( $rows as $row ) {
		$existing = get_page_by_path( $row['slug'], OBJECT, 'du_an' );
		if ( $existing ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'du_an',
				'post_status'  => 'publish',
				'post_name'    => $row['slug'],
				'post_title'   => $row['title'],
				'post_excerpt' => $row['excerpt'],
				'post_content' => '<p>' . esc_html( $note ) . '</p><p>' . esc_html( $row['excerpt'] ) . '</p>',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_sa_du_an_equipment', $row['equipment'] );
		update_post_meta( $id, '_sa_du_an_capacity', $row['capacity'] );
		update_post_meta( $id, '_sa_du_an_region', $row['region'] );
		update_post_meta( $id, '_sa_du_an_featured', $row['featured'] );
		update_post_meta( $id, '_sa_du_an_need', $row['need'] );
		update_post_meta( $id, '_sa_du_an_solution', $row['solution'] );
		update_post_meta( $id, '_sa_du_an_result', $row['result'] );
		update_post_meta( $id, '_sa_du_an_specs', $row['specs'] );
		update_post_meta( $id, '_sa_du_an_gallery', implode( ',', array_map( 'absint', $row['gallery'] ) ) );
		wp_set_object_terms( $id, $row['groups'], 'du_an_nhom' );
		if ( $row['image'] ) {
			set_post_thumbnail( $id, (int) $row['image'] );
		}
		sa_mark_demo( $id );
	}

	update_option( 'sa_du_an_demo_seeded', '1' );
}
add_action( 'init', 'sa_du_an_seed_demo', 40 );
