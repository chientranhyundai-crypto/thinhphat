<?php
/**
 * About page — THỊNH PHÁT. Scoped to /gioi-thieu/.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category archive URL, or a fallback when the term is missing.
 *
 * @param string $slug     Product category slug.
 * @param string $fallback Fallback URL.
 * @return string
 */
function sa_about_term_url( $slug, $fallback ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		return $fallback;
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? $fallback : $link;
}

/**
 * Top-level product categories that actually have products.
 *
 * @return WP_Term[]
 */
function sa_about_catalog_terms() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$exclude = array();
	$default = (int) get_option( 'default_product_cat' );
	if ( $default ) {
		$exclude[] = $default;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'exclude'    => $exclude,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Shortcode: about page.
 *
 * @return string
 */
function sa_about_page_shortcode() {
	$company = sa_business( 'company_name' );
	$shop    = home_url( '/san-pham/' );
	$contact = home_url( '/lien-he/' );

	$cards = array(
		array(
			'title' => 'Máy phát điện',
			'text'  => 'Nguồn chạy độc lập khi điện lưới mất hoặc không đủ ổn định. Danh mục gồm máy công nghiệp, máy chạy xăng, chạy dầu, một pha và ba pha.',
			'url'   => $shop,
			'label' => 'Xem máy phát điện',
		),
		array(
			'title' => 'UPS',
			'text'  => 'Bộ lưu điện online, offline và rack cho tải không chấp nhận gián đoạn trong lúc chuyển nguồn.',
			'url'   => sa_about_term_url( 'bo-luu-dien-hyundai', $shop ),
			'label' => 'Xem bộ lưu điện',
		),
		array(
			'title' => 'Biến tần & thiết bị điện',
			'text'  => 'Thiết bị điều khiển và phụ trợ khi hệ thống cần phối hợp nguồn với phụ tải công nghiệp.',
			'url'   => sa_about_term_url( 'bien-tan', $shop ),
			'label' => 'Xem biến tần',
		),
		array(
			'title' => 'Pin & Ắc quy',
			'text'  => 'Nguồn lưu trữ đi cùng UPS hoặc hệ thống cần duy trì điện trong một khoảng thời gian xác định.',
			'url'   => sa_about_term_url( 'pin-ac-quy', $shop ),
			'label' => 'Xem pin & ắc quy',
		),
	);

	$values = array(
		array(
			'index' => '01',
			'title' => 'Hiểu phụ tải',
			'text'  => 'Bắt đầu từ công suất liên tục, dòng khởi động, số pha và thời gian cần chạy — không từ một model có sẵn.',
		),
		array(
			'index' => '02',
			'title' => 'Chọn đúng giải pháp',
			'text'  => 'Máy phát cho thời gian chạy dài. UPS cho tải không được rớt. Pin và biến tần khi hệ thống cần lưu trữ hoặc điều khiển.',
		),
		array(
			'index' => '03',
			'title' => 'Vận hành ổn định',
			'text'  => 'Đối chiếu thông số công bố của từng model với cách lắp đặt thực tế, để nguồn điện giữ được nhịp vận hành.',
		),
	);

	$steps = array(
		array(
			'title' => 'Tiếp nhận yêu cầu',
			'text'  => 'Ghi nhận loại hình vận hành, vị trí lắp đặt và mức độ gián đoạn có thể chấp nhận.',
		),
		array(
			'title' => 'Phân tích phụ tải',
			'text'  => 'Tách tải liên tục và tải khởi động, rồi xác định điện áp, số pha và thời gian chạy.',
		),
		array(
			'title' => 'Đề xuất thiết bị',
			'text'  => 'Chọn model có thông số phù hợp trong danh mục đang cung cấp, không ước tính số liệu còn thiếu.',
		),
		array(
			'title' => 'Tư vấn triển khai',
			'text'  => 'Làm rõ cách bố trí nguồn, chuyển mạch và những điểm cần chuẩn bị trước khi đưa vào vận hành.',
		),
	);

	ob_start();
	?>
	<article class="sa-about-page">
		<section class="sa-ap-hero" aria-labelledby="sa-ap-title">
			<div class="sa-ap-hero__inner">
				<div class="sa-ap-hero__copy">
					<p class="sa-ap-eyebrow">THỊNH PHÁT · INDUSTRIAL POWER SOLUTIONS</p>
					<h1 id="sa-ap-title">Nguồn điện ổn định.<br>Vận hành bền vững.</h1>
					<p class="sa-ap-lead">THỊNH PHÁT tư vấn máy phát điện, bộ lưu điện UPS và thiết bị nguồn cho doanh nghiệp cần điện liên tục. Mỗi cấu hình được chọn theo phụ tải thật: công suất, số pha và cách vận hành.</p>
					<div class="sa-ap-actions">
						<a class="sa-ap-btn sa-ap-btn--solid" href="<?php echo esc_url( $shop ); ?>">Khám phá sản phẩm</a>
						<a class="sa-ap-btn sa-ap-btn--ghost" href="<?php echo esc_url( $contact ); ?>">Nhận tư vấn</a>
					</div>
				</div>
				<figure class="sa-ap-hero__media">
					<?php
					echo wp_get_attachment_image(
						51,
						'large',
						false,
						array(
							'class'    => 'sa-ap-hero__img',
							'alt'      => 'Tủ điện và hệ thống nguồn công nghiệp trong phòng máy',
							'loading'  => 'eager',
							'decoding' => 'async',
							'sizes'    => '(max-width: 900px) 100vw, 48vw',
						)
					);
					?>
				</figure>
			</div>
		</section>

		<section class="sa-ap-section sa-ap-intro" aria-labelledby="sa-ap-intro-title">
			<div class="sa-ap-section__inner sa-ap-intro__grid">
				<div class="sa-ap-intro__copy">
					<p class="sa-ap-eyebrow">Về Thịnh Phát</p>
					<h2 id="sa-ap-intro-title">Giải pháp nguồn điện bám nhu cầu vận hành</h2>
					<p><?php echo esc_html( $company ); ?> cung cấp máy phát điện và thiết bị nguồn dự phòng cho những nơi mất điện là gián đoạn công việc.</p>
					<p>Định hướng của Thịnh Phát là đề xuất đúng thiết bị cho đúng bài toán: máy chạy xăng cho thời gian ngắn và tải nhỏ, máy chạy dầu khi cần vận hành dài, UPS online khi thiết bị không được ngắt, UPS offline cho tải ít nhạy hơn.</p>
					<p>Thông số trên từng sản phẩm là dữ liệu kỹ thuật đã công bố. Phần chưa có số liệu được để trống, không suy đoán.</p>
				</div>
				<figure class="sa-ap-intro__media">
					<?php
					echo wp_get_attachment_image(
						44,
						'large',
						false,
						array(
							'class'    => 'sa-ap-intro__img',
							'alt'      => 'Dãy tủ nguồn và tủ rack trong phòng thiết bị điện',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 900px) 100vw, 46vw',
						)
					);
					?>
				</figure>
			</div>
		</section>

		<section class="sa-ap-section sa-ap-ability" aria-labelledby="sa-ap-ability-title">
			<div class="sa-ap-section__inner">
				<header class="sa-ap-section__head">
					<p class="sa-ap-eyebrow">Năng lực giải pháp</p>
					<h2 id="sa-ap-ability-title">Bốn nhóm thiết bị cho một hệ thống nguồn</h2>
				</header>
				<div class="sa-ap-cards">
					<?php foreach ( $cards as $card ) : ?>
						<article class="sa-ap-card">
							<h3><?php echo esc_html( $card['title'] ); ?></h3>
							<p><?php echo esc_html( $card['text'] ); ?></p>
							<a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['label'] ); ?></a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="sa-ap-section sa-ap-belief" aria-labelledby="sa-ap-belief-title">
			<div class="sa-ap-section__inner">
				<header class="sa-ap-section__head sa-ap-section__head--center">
					<p class="sa-ap-eyebrow">Triết lý</p>
					<h2 id="sa-ap-belief-title">Đúng thiết bị – Đúng công suất – Đúng nhu cầu vận hành.</h2>
				</header>
				<ol class="sa-ap-values">
					<?php foreach ( $values as $value ) : ?>
						<li>
							<span><?php echo esc_html( $value['index'] ); ?></span>
							<h3><?php echo esc_html( $value['title'] ); ?></h3>
							<p><?php echo esc_html( $value['text'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>

		<section class="sa-ap-section sa-ap-process" aria-labelledby="sa-ap-process-title">
			<div class="sa-ap-section__inner">
				<header class="sa-ap-section__head">
					<p class="sa-ap-eyebrow">Quy trình</p>
					<h2 id="sa-ap-process-title">Từ yêu cầu đến phương án nguồn điện</h2>
				</header>
				<ol class="sa-ap-steps">
					<?php foreach ( $steps as $i => $step ) : ?>
						<li>
							<span class="sa-ap-steps__index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<h3><?php echo esc_html( $step['title'] ); ?></h3>
							<p><?php echo esc_html( $step['text'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>

		<?php $terms = sa_about_catalog_terms(); ?>
		<?php if ( $terms ) : ?>
			<section class="sa-ap-section sa-ap-catalog" aria-labelledby="sa-ap-catalog-title">
				<div class="sa-ap-section__inner">
					<header class="sa-ap-section__head">
						<p class="sa-ap-eyebrow">Sản phẩm / thương hiệu</p>
						<h2 id="sa-ap-catalog-title">Danh mục đang có trên website</h2>
						<p class="sa-ap-section__note">Các nhóm dưới đây là danh mục sản phẩm đã đăng. Bấm vào tên để xem đúng sản phẩm trong nhóm đó.</p>
					</header>
					<ul class="sa-ap-brands">
						<?php foreach ( $terms as $term ) : ?>
							<?php
							$link  = get_term_link( $term );
							$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
							if ( is_wp_error( $link ) ) {
								continue;
							}
							?>
							<li>
								<a class="sa-ap-brand" href="<?php echo esc_url( $link ); ?>">
									<?php if ( $thumb ) : ?>
										<span class="sa-ap-brand__media">
											<?php
											echo wp_get_attachment_image(
												$thumb,
												'medium',
												false,
												array(
													'alt'      => '',
													'loading'  => 'lazy',
													'decoding' => 'async',
												)
											);
											?>
										</span>
									<?php endif; ?>
									<span class="sa-ap-brand__name"><?php echo esc_html( $term->name ); ?></span>
									<span class="sa-ap-brand__count"><?php echo esc_html( sprintf( '%d sản phẩm', (int) $term->count ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<section class="sa-ap-cta" aria-labelledby="sa-ap-cta-title">
			<div class="sa-ap-cta__inner">
				<h2 id="sa-ap-cta-title">Bạn đang cần một hệ thống nguồn điện phù hợp?</h2>
				<p>Cho Thịnh Phát biết phụ tải và cách bạn vận hành. Chúng tôi đề xuất thiết bị có trong danh mục, với thông số đủ để đối chiếu.</p>
				<div class="sa-ap-actions">
					<a class="sa-ap-btn sa-ap-btn--solid" href="<?php echo esc_url( $contact ); ?>">Nhận tư vấn</a>
					<a class="sa-ap-btn sa-ap-btn--ghost" href="<?php echo esc_url( $shop ); ?>">Xem sản phẩm</a>
				</div>
			</div>
		</section>
	</article>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_about_page', 'sa_about_page_shortcode' );

/**
 * About page assets and document title.
 */
function sa_about_is_page() {
	return is_page( 'gioi-thieu' );
}

/**
 * SEO title for the about page.
 *
 * @param array $parts Title parts.
 * @return array
 */
function sa_about_document_title( $parts ) {
	if ( sa_about_is_page() ) {
		$parts['title'] = 'Giới thiệu — Bộ lưu điện, biến tần, pin và ắc quy';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sa_about_document_title' );

/**
 * Meta description. No invented history, projects, or certificates.
 */
function sa_about_meta_description() {
	if ( ! sa_about_is_page() ) {
		return;
	}
	$description = 'THỊNH PHÁT tư vấn bộ lưu điện UPS, biến tần, pin và ắc quy theo phụ tải thực tế. Chọn đúng thiết bị, đúng công suất, đúng cách vận hành.';
	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}
add_action( 'wp_head', 'sa_about_meta_description', 1 );
