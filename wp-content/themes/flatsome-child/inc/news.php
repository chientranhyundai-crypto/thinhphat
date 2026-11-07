<?php
/**
 * News page /tin-tuc/ and single posts.
 * Cards come from published posts. Demo articles are seeded once.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Designed filter groups. Extra categories with posts are appended.
 *
 * @return array<string,string>
 */
function sa_news_groups() {
	return array(
		'may-phat-dien' => 'Máy phát điện',
		'ups'           => 'UPS',
		'kien-thuc'     => 'Kiến thức',
		'tu-van'        => 'Tư vấn',
		'cong-nghe'     => 'Công nghệ',
	);
}

/**
 * @return WP_Term[]
 */
function sa_news_filter_terms() {
	$out  = array();
	$seen = array();
	foreach ( sa_news_groups() as $slug => $name ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$out[]            = $term;
		$seen[ $term->term_id ] = true;
	}
	$extra = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
		)
	);
	if ( is_array( $extra ) ) {
		foreach ( $extra as $term ) {
			if ( isset( $seen[ $term->term_id ] ) || 'chua-phan-loai' === $term->slug || 'uncategorized' === $term->slug ) {
				continue;
			}
			$out[] = $term;
		}
	}
	return $out;
}

/**
 * @param string $slug Category slug.
 * @param int    $page Page number.
 * @return string
 */
function sa_news_url( $slug = '', $page = 1 ) {
	$url  = home_url( '/tin-tuc/' );
	$args = array();
	if ( $slug ) {
		$args['chuyen-muc'] = $slug;
	}
	if ( $page > 1 ) {
		$args['trang'] = $page;
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

/**
 * @return string
 */
function sa_news_current_slug() {
	$slug = isset( $_GET['chuyen-muc'] ) ? sanitize_title( wp_unslash( $_GET['chuyen-muc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $slug ) {
		return '';
	}
	$term = get_term_by( 'slug', $slug, 'category' );
	return ( $term && ! is_wp_error( $term ) ) ? $slug : '';
}

/**
 * @return int
 */
function sa_news_current_page() {
	$page = isset( $_GET['trang'] ) ? absint( $_GET['trang'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return max( 1, $page );
}

/**
 * @param string $slug Category slug or empty.
 * @return array<string,mixed>
 */
function sa_news_tax_query( $slug ) {
	if ( ! $slug ) {
		return array();
	}
	return array(
		array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => $slug,
		),
	);
}

/**
 * Three posts for the top row. Marked posts come first, then the latest.
 *
 * @param string $slug Category slug.
 * @return WP_Post[]
 */
function sa_news_featured_posts( $slug ) {
	$base = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	$tax = sa_news_tax_query( $slug );
	if ( $tax ) {
		$base['tax_query'] = $tax;
	}

	$marked = get_posts(
		array_merge(
			$base,
			array(
				'posts_per_page' => 3,
				'meta_key'       => '_sa_news_featured',
				'meta_value'     => '1',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		)
	);
	$ids = wp_list_pluck( $marked, 'ID' );
	$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
	if ( $sticky && count( $marked ) < 3 ) {
		$extra = get_posts(
			array_merge(
				$base,
				array(
					'posts_per_page' => 3,
					'post__in'       => $sticky,
					'post__not_in'   => $ids ? $ids : array( 0 ),
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			)
		);
		$marked = array_merge( $marked, $extra );
		$ids    = wp_list_pluck( $marked, 'ID' );
	}
	$marked = array_slice( $marked, 0, 3 );
	if ( count( $marked ) < 3 ) {
		$fill = get_posts(
			array_merge(
				$base,
				array(
					'posts_per_page' => 3 - count( $marked ),
					'post__not_in'   => $ids ? $ids : array( 0 ),
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			)
		);
		$marked = array_merge( $marked, $fill );
	}
	return $marked;
}

/**
 * @param int $post_id Post ID.
 * @return string
 */
function sa_news_cat_name( $post_id ) {
	$terms = get_the_terms( $post_id, 'category' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	$current = sa_news_current_slug();
	foreach ( $terms as $term ) {
		if ( $current && $term->slug === $current ) {
			return $term->name;
		}
	}
	foreach ( $terms as $term ) {
		if ( 'chua-phan-loai' !== $term->slug && 'uncategorized' !== $term->slug ) {
			return $term->name;
		}
	}
	return $terms[0]->name;
}

/**
 * @param int    $post_id Post ID.
 * @param string $size    Image size.
 * @return string
 */
function sa_news_thumb( $post_id, $size ) {
	$alt = get_the_title( $post_id );
	if ( function_exists( 'sa_is_demo' ) && sa_is_demo( $post_id ) ) {
		$alt .= ' — ảnh minh họa';
	}
	$html = get_the_post_thumbnail(
		$post_id,
		$size,
		array(
			'class'    => 'sa-news-img',
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
	if ( $html ) {
		return $html;
	}
	return '<span class="sa-news-ph" aria-hidden="true"></span>';
}

/**
 * @param WP_Post $post    Post.
 * @param string  $variant feature, side, or card.
 * @return string
 */
function sa_news_card( $post, $variant ) {
	$link  = get_permalink( $post );
	$cat   = sa_news_cat_name( $post->ID );
	$demo  = function_exists( 'sa_is_demo' ) && sa_is_demo( $post->ID );
	$raw   = $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );
	$text  = wp_trim_words( $raw, 'feature' === $variant ? 32 : 22, '…' );
	$date  = get_the_date( 'd/m/Y', $post );
	$title = 'card' === $variant ? 'h3' : ( 'feature' === $variant ? 'h2' : 'h3' );
	$label = 'feature' === $variant ? 'Đọc bài' : 'Đọc tiếp';
	ob_start();
	?>
	<article class="sa-news-card sa-news-card--<?php echo esc_attr( $variant ); ?>">
		<a class="sa-news-card__media" href="<?php echo esc_url( $link ); ?>">
			<?php echo sa_news_thumb( $post->ID, 'feature' === $variant ? 'large' : 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="sa-news-card__arrow" aria-hidden="true">→</span>
			<?php if ( $demo ) : ?><span class="sa-news-badge">DEMO</span><?php endif; ?>
		</a>
		<div class="sa-news-card__body">
			<?php if ( $cat ) : ?><p class="sa-news-cat"><?php echo esc_html( $cat ); ?></p><?php endif; ?>
			<<?php echo esc_html( $title ); ?> class="sa-news-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></<?php echo esc_html( $title ); ?>>
			<?php if ( $text ) : ?><p class="sa-news-excerpt"><?php echo esc_html( $text ); ?></p><?php endif; ?>
			<p class="sa-news-meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( $date ); ?></time>
				<a class="sa-news-more" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $label ); ?> →</a>
			</p>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * @return string
 */
function sa_news_page_shortcode() {
	$slug     = sa_news_current_slug();
	$page     = sa_news_current_page();
	$featured = sa_news_featured_posts( $slug );
	$exclude  = wp_list_pluck( $featured, 'ID' );
	$args     = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'paged'               => $page,
		'ignore_sticky_posts' => true,
		'post__not_in'        => $exclude ? $exclude : array( 0 ),
	);
	$tax = sa_news_tax_query( $slug );
	if ( $tax ) {
		$args['tax_query'] = $tax;
	}
	$query   = new WP_Query( $args );
	$links   = array(
		'contact' => home_url( '/lien-he/' ),
		'hotline' => sa_business( 'hotline' ),
		'tel'     => preg_replace( '/\D+/', '', sa_business( 'hotline_tel' ) ),
	);
	$large   = $featured ? $featured[0] : null;
	$side    = array_slice( $featured, 1, 2 );

	ob_start();
	?>
	<article class="sa-news">
		<section class="sa-news-hero" aria-labelledby="sa-news-title">
			<div class="sa-news-wrap">
				<?php echo sa_news_crumbs( array( array( 'Tin tức', '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="sa-news-eyebrow">Kiến thức &amp; tin tức</p>
				<h1 id="sa-news-title">Kiến thức giúp bạn lựa chọn giải pháp nguồn điện tốt hơn</h1>
				<p class="sa-news-lead">Một số nguyên tắc để nhìn phụ tải, số pha, UPS và máy phát trước khi chọn thiết bị. Bài viết nói cách đối chiếu, không thay cho tài liệu của từng model.</p>
			</div>
		</section>

		<section class="sa-news-board" aria-label="Danh sách bài viết">
			<div class="sa-news-wrap">
				<nav class="sa-news-filters" aria-label="Chuyên mục">
					<a href="<?php echo esc_url( sa_news_url() ); ?>" <?php echo $slug ? '' : 'aria-current="page"'; ?> class="<?php echo $slug ? '' : 'is-active'; ?>">Tất cả</a>
					<?php foreach ( sa_news_filter_terms() as $term ) : ?>
						<a href="<?php echo esc_url( sa_news_url( $term->slug ) ); ?>" <?php echo $slug === $term->slug ? 'aria-current="page" class="is-active"' : ''; ?>><?php echo esc_html( $term->name ); ?></a>
					<?php endforeach; ?>
				</nav>

				<?php if ( 1 === $page && $large ) : ?>
					<div class="sa-news-feature">
						<?php echo sa_news_card( $large, 'feature' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $side ) : ?>
							<div class="sa-news-feature__side">
								<?php foreach ( $side as $post ) : ?>
									<?php echo sa_news_card( $post, 'side' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $query->have_posts() ) : ?>
					<h2 id="sa-news-list-title">Bài viết</h2>
					<div class="sa-news-grid">
						<?php foreach ( $query->posts as $post ) : ?>
							<?php echo sa_news_card( $post, 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
					<?php echo sa_news_pagination( $query, $slug, $page ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php elseif ( ! $large ) : ?>
					<p class="sa-news-empty">Chưa có bài viết trong chuyên mục này.</p>
				<?php endif; ?>
			</div>
		</section>

		<?php echo sa_news_cta( $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	wp_reset_postdata();
	return (string) ob_get_clean();
}
add_shortcode( 'sa_news_page', 'sa_news_page_shortcode' );

/**
 * Category and page 2+ keep the existing renderer.
 * The default listing is native UX Builder content.
 *
 * @param string $content Post content.
 * @return string
 */
function sa_news_dynamic_listing( $content ) {
	if ( ! is_page( 'tin-tuc' ) ) {
		return $content;
	}
	if ( is_admin() || ( function_exists( 'ux_builder_is_active' ) && ux_builder_is_active() ) ) {
		return $content;
	}
	if ( sa_news_current_slug() || sa_news_current_page() > 1 ) {
		return sa_news_page_shortcode();
	}
	return $content;
}
add_filter( 'the_content', 'sa_news_dynamic_listing', 12 );

/**
 * @param array<int,array{0:string,1:string}> $items Name and URL. Empty URL is the current item.
 * @return string
 */
function sa_news_crumbs( $items ) {
	$list = array_merge( array( array( 'Trang chủ', home_url( '/' ) ) ), $items );
	$html = '<nav class="sa-news-crumb" aria-label="Breadcrumb"><ol>';
	$last = count( $list ) - 1;
	foreach ( $list as $i => $item ) {
		$html .= '<li>';
		if ( $i === $last || '' === $item[1] ) {
			$html .= '<span aria-current="page">' . esc_html( $item[0] ) . '</span>';
		} else {
			$html .= '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>';
		}
		$html .= '</li>';
	}
	$html .= '</ol></nav>';
	return $html;
}

/**
 * @param WP_Query $query Query.
 * @param string   $slug  Category slug.
 * @param int      $page  Current page.
 * @return string
 */
function sa_news_pagination( $query, $slug, $page ) {
	$total = (int) $query->max_num_pages;
	if ( $total < 2 ) {
		return '';
	}
	$html = '<nav class="sa-news-pager" aria-label="Phân trang">';
	if ( $page > 1 ) {
		$html .= '<a href="' . esc_url( sa_news_url( $slug, $page - 1 ) ) . '">Trước</a>';
	}
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( $i === $page ) {
			$html .= '<span aria-current="page">' . esc_html( (string) $i ) . '</span>';
		} else {
			$html .= '<a href="' . esc_url( sa_news_url( $slug, $i ) ) . '">' . esc_html( (string) $i ) . '</a>';
		}
	}
	if ( $page < $total ) {
		$html .= '<a href="' . esc_url( sa_news_url( $slug, $page + 1 ) ) . '">Sau</a>';
	}
	$html .= '</nav>';
	return $html;
}

/**
 * @param array{contact:string,hotline:string,tel:string} $links Links.
 * @return string
 */
function sa_news_cta( $links ) {
	ob_start();
	?>
	<section class="sa-news-cta" aria-labelledby="sa-news-cta-title">
		<div class="sa-news-wrap">
			<h2 id="sa-news-cta-title">Bạn cần tư vấn giải pháp nguồn điện?</h2>
			<div class="sa-news-cta__row">
				<a class="sa-news-btn" href="<?php echo esc_url( $links['contact'] ); ?>">NHẬN TƯ VẤN →</a>
				<?php if ( $links['hotline'] && $links['tel'] ) : ?>
					<p>Hotline: <a href="<?php echo esc_url( 'tel:' . $links['tel'] ); ?>"><?php echo esc_html( $links['hotline'] ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Single article.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function sa_news_render_single( $post_id ) {
	$post    = get_post( $post_id );
	$cat     = sa_news_cat_name( $post_id );
	$terms   = get_the_terms( $post_id, 'category' );
	$slug    = '';
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( isset( sa_news_groups()[ $term->slug ] ) ) {
				$slug = $term->slug;
				break;
			}
		}
		if ( ! $slug ) {
			$slug = $terms[0]->slug;
		}
	}
	$related = sa_news_related( $post_id, $slug );
	$links   = array(
		'contact' => home_url( '/lien-he/' ),
		'hotline' => sa_business( 'hotline' ),
		'tel'     => preg_replace( '/\D+/', '', sa_business( 'hotline_tel' ) ),
	);
	$crumbs  = array( array( 'Tin tức', home_url( '/tin-tuc/' ) ) );
	if ( $cat && $slug ) {
		$crumbs[] = array( $cat, sa_news_url( $slug ) );
	}
	$crumbs[] = array( get_the_title( $post ), '' );

	ob_start();
	?>
	<article class="sa-news sa-news-single">
		<header class="sa-news-hero sa-news-hero--single">
			<div class="sa-news-wrap sa-news-wrap--narrow">
				<?php echo sa_news_crumbs( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( function_exists( 'sa_is_demo' ) && sa_is_demo( $post_id ) ) : ?><p class="sa-news-badge sa-news-badge--inline">DEMO</p><?php endif; ?>
				<?php if ( $cat ) : ?><p class="sa-news-cat"><?php echo esc_html( $cat ); ?></p><?php endif; ?>
				<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
				<p class="sa-news-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $post ) ); ?></time></p>
			</div>
		</header>
		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
			<figure class="sa-news-cover">
				<?php echo sa_news_thumb( $post_id, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</figure>
		<?php endif; ?>
		<div class="sa-news-wrap sa-news-wrap--narrow sa-news-prose">
			<?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( $related ) : ?>
			<section class="sa-news-related" aria-labelledby="sa-news-related">
				<div class="sa-news-wrap">
					<h2 id="sa-news-related">Bài viết liên quan</h2>
					<div class="sa-news-grid">
						<?php foreach ( $related as $item ) : ?>
							<?php echo sa_news_card( $item, 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php echo sa_news_cta( $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param int    $post_id Current post.
 * @param string $slug    Category slug.
 * @return WP_Post[]
 */
function sa_news_related( $post_id, $slug ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $slug && 'chua-phan-loai' !== $slug && 'uncategorized' !== $slug ) {
		$args['tax_query'] = sa_news_tax_query( $slug );
	}
	$posts = get_posts( $args );
	if ( count( $posts ) < 3 ) {
		$more = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 3 - count( $posts ),
				'post__not_in'        => array_merge( array( $post_id ), wp_list_pluck( $posts, 'ID' ) ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		$posts = array_merge( $posts, $more );
	}
	return $posts;
}

/**
 * @param string[] $classes Classes.
 * @return string[]
 */
function sa_news_body_class( $classes ) {
	if ( is_page( 'tin-tuc' ) || is_singular( 'post' ) ) {
		$classes[] = 'sa-news-page';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_news_body_class' );

/**
 * @param array $parts Title parts.
 * @return array
 */
function sa_news_document_title( $parts ) {
	if ( is_page( 'tin-tuc' ) ) {
		$parts['title'] = 'Kiến thức và tin tức nguồn điện';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sa_news_document_title' );

/**
 * Archive and single descriptions.
 */
function sa_news_meta_description() {
	if ( is_page( 'tin-tuc' ) ) {
		$description = 'Bài viết về bộ lưu điện, biến tần, pin và ắc quy. Thịnh Phát giải thích nguyên tắc đối chiếu phụ tải, không bịa thông số của từng model.';
	} elseif ( is_singular( 'post' ) ) {
		$description = wp_strip_all_tags( get_the_excerpt() );
	} else {
		return;
	}
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sa_news_meta_description', 1 );

/**
 * Article schema on single posts.
 */
function sa_news_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$id    = get_the_ID();
	$brand = sa_business( 'brand' );
	$data  = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => get_the_title( $id ),
		'datePublished'    => get_post_time( 'c', true, $id ),
		'dateModified'     => get_post_modified_time( 'c', true, $id ),
		'mainEntityOfPage' => get_permalink( $id ),
		'description'      => wp_strip_all_tags( get_the_excerpt( $id ) ),
		'author'           => array(
			'@type' => 'Organization',
			'name'  => $brand,
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => $brand,
		),
	);
	$image = get_the_post_thumbnail_url( $id, 'large' );
	if ( $image ) {
		$data['image'] = $image;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'sa_news_schema', 20 );

/**
 * Featured checkbox.
 */
function sa_news_metabox() {
	add_meta_box(
		'sa_news_featured',
		'Tin tức',
		function ( $post ) {
			wp_nonce_field( 'sa_news_featured', 'sa_news_featured_nonce' );
			$on = '1' === (string) get_post_meta( $post->ID, '_sa_news_featured', true );
			echo '<label><input type="checkbox" name="sa_news_featured" value="1" ' . checked( $on, true, false ) . '> Bài nổi bật trên trang Tin tức</label>';
			echo '<p class="description">Chọn tối đa 3 bài. Bài được đánh dấu hiện ở khối phía trên danh sách.</p>';
		},
		'post',
		'side'
	);
}
add_action( 'add_meta_boxes', 'sa_news_metabox' );

/**
 * @param int $post_id Post ID.
 */
function sa_news_save_featured( $post_id ) {
	if ( ! isset( $_POST['sa_news_featured_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sa_news_featured_nonce'] ) ), 'sa_news_featured' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_sa_news_featured', empty( $_POST['sa_news_featured'] ) ? '0' : '1' );
}
add_action( 'save_post_post', 'sa_news_save_featured' );

/**
 * Create categories and eight DEMO articles once.
 */
function sa_news_seed_demo() {
	if ( '1' === get_option( 'sa_news_demo_seeded' ) ) {
		return;
	}
	$ids = array();
	foreach ( sa_news_groups() as $slug => $name ) {
		$term = term_exists( $slug, 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
		if ( ! is_wp_error( $term ) ) {
			$ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}
	}

	$shop    = home_url( '/san-pham/' );
	$enterprise = home_url( '/giai-phap/giai-phap-doanh-nghiep/' );
	$industrial = home_url( '/giai-phap/giai-phap-cong-nghiep/' );
	$note    = 'Bài viết minh họa (DEMO) để hoàn thiện mục tin tức. Nội dung là nguyên tắc chung, không phải thông số của một model và không gắn với công trình nào.';

	$posts = array(
		array(
			'slug'    => 'demo-cach-chon-cong-suat-may-phat-dien',
			'title'   => 'DEMO — Cách chọn công suất máy phát điện',
			'excerpt' => 'Phân biệt công suất liên tục và công suất dự phòng, rồi đối chiếu với danh sách tải. Không chọn máy chỉ vì một con số watt chưa kiểm.',
			'cats'    => array( 'may-phat-dien', 'kien-thuc' ),
			'image'   => 310,
			'content' => '<p>' . $note . '</p><p>Công suất ghi trên máy thường có hai mức: chạy liên tục và chạy dự phòng. Hai mức này không thay thế cho nhau. Máy chọn theo mức tương ứng với số giờ cần chạy, không theo nhãn lớn hơn cho có.</p><h2>Cần đối chiếu những gì</h2><ul><li>Liệt kê thiết bị phải chạy tiếp và thiết bị được phép dừng.</li><li>Tách tải chạy ổn định với tải có dòng khởi động, như động cơ.</li><li>Xem số pha và điện áp của tải có trùng máy hay không.</li><li>Đọc đúng dòng công suất trên tài liệu của model định chọn.</li></ul><p>Chưa có danh sách tải thì chưa đủ căn cứ để chốt máy. <a href="' . esc_url( $shop ) . '">Xem máy phát điện đang có</a> hoặc <a href="' . esc_url( $industrial ) . '">xem giải pháp cho hệ thống công nghiệp</a>.</p>',
		),
		array(
			'slug'    => 'demo-may-phat-dien-1-pha-va-3-pha',
			'title'   => 'DEMO — Máy phát điện 1 pha và 3 pha',
			'excerpt' => 'Số pha của máy phải theo hệ thống điện và loại tải, không suy từ công suất. Động cơ ba pha cần nguồn ba pha.',
			'cats'    => array( 'may-phat-dien', 'kien-thuc' ),
			'image'   => 312,
			'content' => '<p>' . $note . '</p><p>Máy một pha cấp cho tải một pha. Máy ba pha cấp cho hệ thống ba pha, và có thể chia ra tải một pha nếu cách đấu cho phép và các pha không lệch quá mức chịu được.</p><h2>Không chọn số pha theo cảm tính</h2><ul><li>Động cơ ghi 3 pha thì không chạy trên nguồn 1 pha nếu không có thiết bị biến đổi phù hợp.</li><li>Công suất lớn không đồng nghĩa với ba pha, và máy nhỏ không mặc định là một pha.</li><li>Khi trên cùng một bảng điện có cả hai loại tải, cần người tính phân pha trước khi chọn máy.</li></ul><p>Đối chiếu trên catalog theo đúng số pha. <a href="' . esc_url( $shop ) . '">Mở danh mục máy phát điện</a>.</p>',
		),
		array(
			'slug'    => 'demo-may-phat-dien-chay-dau',
			'title'   => 'DEMO — Máy phát điện chạy dầu',
			'excerpt' => 'Máy chạy dầu thường được xét khi cần thời gian chạy dài hoặc tải lớn. Suất tiêu hao chỉ lấy từ tài liệu của đúng model.',
			'cats'    => array( 'may-phat-dien', 'tu-van' ),
			'image'   => 451,
			'content' => '<p>' . $note . '</p><p>Nhiên liệu không phải tiêu chí duy nhất. Máy chạy dầu hay chạy xăng còn khác nhau ở dải công suất, cách khởi động và điều kiện đặt máy. Không lấy mức tiêu hao của máy này gán cho máy khác.</p><h2>Khi đọc một model chạy dầu</h2><ul><li>Xem công suất liên tục, không chỉ công suất dự phòng.</li><li>Xem dung tích nhiên liệu và thời gian chạy nếu hãng có ghi. Không có số thì để trống.</li><li>Chừa lối thông gió và khoảng cách theo hướng dẫn lắp đặt.</li><li>Nhiên liệu và nhớt theo đúng chủng loại hãng yêu cầu.</li></ul><p><a href="' . esc_url( $shop ) . '">Xem các máy đang có trong danh mục</a>.</p>',
		),
		array(
			'slug'    => 'demo-cach-lua-chon-ups',
			'title'   => 'DEMO — Cách lựa chọn UPS',
			'excerpt' => 'UPS được chọn theo tải cần giữ, thời gian cần chạy và việc tải có chịu được khoảng ngắt hay không.',
			'cats'    => array( 'ups', 'tu-van' ),
			'image'   => 366,
			'content' => '<p>' . $note . '</p><p>UPS không thay máy phát khi mất điện nhiều giờ. UPS giữ tải trong lúc chuyển nguồn, hoặc đủ lâu để lưu việc và tắt thiết bị. Thời gian đó phụ thuộc tải thực và bộ ắc quy đi kèm.</p><h2>Ba câu trước khi chọn</h2><ul><li>Thiết bị nào không được ngắt, dù rất ngắn?</li><li>Tổng tải những thiết bị đó là bao nhiêu, đo hoặc cộng từ nhãn thiết bị?</li><li>Cần vài phút để tắt máy, hay cần chạy tiếp đến khi máy phát vào tải?</li></ul><p>Online, offline và rack là ba dạng khác nhau, không phải ba mức tốt xấu. <a href="' . esc_url( $enterprise ) . '">Xem giải pháp nguồn cho doanh nghiệp</a>.</p>',
		),
		array(
			'slug'    => 'demo-ups-online-la-gi',
			'title'   => 'DEMO — UPS Online là gì?',
			'excerpt' => 'UPS online cấp điện cho tải qua bộ nghịch lưu. Tải không phụ thuộc vào việc chuyển mạch sang ắc quy như nhiều UPS offline.',
			'cats'    => array( 'ups', 'kien-thuc' ),
			'image'   => 337,
			'content' => '<p>' . $note . '</p><p>Với UPS online, điện lưới được nắn và nghịch lưu lại trước khi tới tải. Khi lưới mất, ắc quy tiếp tục nuôi bộ nghịch lưu. Tải vì thế không phải chờ một lần chuyển nguồn theo kiểu UPS offline.</p><h2>Không suy ra thông số</h2><p>Thời gian lưu điện, công suất và dạng sóng phải đọc trên tài liệu của model. Bài này không đưa một con số dùng chung cho mọi UPS online. Máy chủ, thiết bị mạng và tủ điều khiển là những tải thường được xét dạng này khi không chấp nhận gián đoạn.</p><p><a href="' . esc_url( $shop ) . '">Xem bộ lưu điện trong danh mục</a>.</p>',
		),
		array(
			'slug'    => 'demo-bao-duong-may-phat-dien',
			'title'   => 'DEMO — Bảo dưỡng máy phát điện',
			'excerpt' => 'Lịch bảo dưỡng theo tài liệu của hãng cho đúng model. Bài này chỉ liệt kê các hạng mục thường được kiểm tra, không đặt chu kỳ thay cho nhà sản xuất.',
			'cats'    => array( 'may-phat-dien', 'tu-van' ),
			'image'   => 444,
			'content' => '<p>' . $note . '</p><p>Mỗi hãng có mốc giờ chạy và hạng mục riêng. Dùng lịch của model đang vận hành. Không copy chu kỳ của một dòng máy khác.</p><h2>Hạng mục thường được xem</h2><ul><li>Mức và tình trạng nhớt, nước làm mát, nhiên liệu.</li><li>Lọc gió, lọc nhiên liệu, dây đai nếu máy có những hạng mục này.</li><li>Ắc quy đề, điểm nối và tình trạng sạc.</li><li>Lối gió, độ sạch của két và dấu rò rỉ.</li><li>Thử khởi động theo hướng dẫn, không chạy quá tải để “kiểm tra”.</li></ul><p>Việc siết, cân chỉnh và thay thế bên trong máy để kỹ thuật phụ trách khi đến hạn.</p>',
		),
		array(
			'slug'    => 'demo-giai-phap-nguon-dien-cho-nha-may',
			'title'   => 'DEMO — Giải pháp nguồn điện cho nhà máy',
			'excerpt' => 'Nhà máy thường cần tách tải được phép dừng, tải chạy dài bằng máy phát và tải không được ngắt bằng UPS. Đây là cách đặt bài toán, không phải một công trình đã làm.',
			'cats'    => array( 'may-phat-dien', 'cong-nghe' ),
			'image'   => 141,
			'content' => '<p>' . $note . '</p><p>Một nhà máy có nhiều loại tải. Dây chuyền, bơm, chiếu sáng và tủ điều khiển không dùng chung một cách giữ nguồn. Gộp tất cả vào một con số rồi nhân thêm một tỷ lệ cố định dễ chọn sai máy.</p><h2>Thứ tự hợp lý</h2><ul><li>Liệt kê thiết bị dừng được và thiết bị phải chạy.</li><li>Tách thiết bị không chịu khoảng ngắt, thường là điều khiển và mạng, để xét UPS.</li><li>Phần cần chạy lâu khi mất lưới đưa sang máy phát, sau khi đã có dòng khởi động.</li><li>Chốt model bằng thông số đang công bố, không bằng tên công suất gọi cho nhanh.</li></ul><p><a href="' . esc_url( $industrial ) . '">Xem giải pháp nguồn điện cho hệ thống công nghiệp</a>.</p>',
		),
		array(
			'slug'    => 'demo-loi-thuong-gap-khi-dung-may-phat-dien',
			'title'   => 'DEMO — Những lỗi thường gặp khi sử dụng máy phát điện',
			'excerpt' => 'Một số lỗi lặp lại: chọn nhầm mức công suất, sai số pha, đặt máy nơi bí gió và chạy quá tải. Không gắn với một hiện trường cụ thể.',
			'cats'    => array( 'may-phat-dien', 'kien-thuc' ),
			'image'   => 450,
			'content' => '<p>' . $note . '</p><h2>Những chỗ dễ chọn sai</h2><ul><li>Lấy công suất dự phòng làm công suất chạy cả ca.</li><li>Cộng watt của mọi thiết bị rồi bỏ qua dòng khởi động của động cơ.</li><li>Chọn máy một pha cho tải ba pha, hoặc ngược lại, chỉ vì công suất trông gần nhau.</li><li>Đặt máy trong phòng kín, hết lối thải gió.</li><li>Chạy quá tải để “xem máy chịu được không”.</li><li>Bỏ qua hướng dẫn nhiên liệu và nhớt của đúng model.</li></ul><p>Khi một trong các mục trên chưa rõ, nên dừng ở bước đối chiếu trước khi mua. <a href="' . esc_url( home_url( '/lien-he/' ) ) . '">Gửi yêu cầu tư vấn</a>.</p>',
		),
	);

	foreach ( $posts as $row ) {
		$existing = get_page_by_path( $row['slug'], OBJECT, 'post' );
		if ( $existing ) {
			continue;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_name'    => $row['slug'],
				'post_title'   => $row['title'],
				'post_excerpt' => $row['excerpt'],
				'post_content' => $row['content'],
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		$cat_ids = array();
		foreach ( $row['cats'] as $cat_slug ) {
			if ( ! empty( $ids[ $cat_slug ] ) ) {
				$cat_ids[] = $ids[ $cat_slug ];
			}
		}
		if ( $cat_ids ) {
			wp_set_post_categories( $post_id, $cat_ids, false );
		}
		if ( ! empty( $row['image'] ) && wp_attachment_is_image( $row['image'] ) ) {
			set_post_thumbnail( $post_id, (int) $row['image'] );
		}
		if ( function_exists( 'sa_mark_demo' ) ) {
			sa_mark_demo( $post_id );
		}
	}

	$assign = array(
		388 => array( 'may-phat-dien', 'kien-thuc', 310 ),
		389 => array( 'may-phat-dien', 'kien-thuc', 312 ),
		390 => array( 'may-phat-dien', 'tu-van', 451 ),
		119 => array( 'ups', 'tu-van', 0 ),
		120 => array( 'ups', 'kien-thuc', 0 ),
		121 => array( 'ups', 'kien-thuc', 0 ),
		122 => array( 'ups', 'tu-van', 0 ),
		123 => array( 'ups', 'kien-thuc', 0 ),
		124 => array( 'cong-nghe', 'tu-van', 0 ),
		125 => array( 'ups', 'cong-nghe', 0 ),
	);
	foreach ( $assign as $post_id => $row ) {
		if ( 'post' !== get_post_type( $post_id ) ) {
			continue;
		}
		$cat_ids = array();
		foreach ( array( $row[0], $row[1] ) as $cat_slug ) {
			if ( ! empty( $ids[ $cat_slug ] ) ) {
				$cat_ids[] = $ids[ $cat_slug ];
			}
		}
		if ( $cat_ids ) {
			wp_set_post_categories( $post_id, $cat_ids, false );
		}
		if ( $row[2] && ! get_post_thumbnail_id( $post_id ) && wp_attachment_is_image( $row[2] ) ) {
			set_post_thumbnail( $post_id, (int) $row[2] );
		}
	}
	if ( 'post' === get_post_type( 388 ) ) {
		update_post_meta( 388, '_sa_news_featured', '1' );
	}

	update_option( 'sa_news_demo_seeded', '1' );
}
add_action( 'init', 'sa_news_seed_demo', 40 );
