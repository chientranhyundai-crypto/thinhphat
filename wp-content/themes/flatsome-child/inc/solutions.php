<?php
/**
 * Solution landings: /giai-phap/giai-phap-doanh-nghiep/ and /giai-phap/giai-phap-cong-nghiep/.
 * Product links and brand images come from WooCommerce categories.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $slug Product category slug.
 * @return array{name:string,url:string,thumb:int}|null
 */
function sa_solution_cat( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) || (int) $term->count < 1 ) {
		return null;
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		$link = home_url( '/san-pham/' );
	}
	$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( ! $thumb ) {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $slug,
					),
				),
			)
		);
		foreach ( $ids as $id ) {
			$tid = (int) get_post_thumbnail_id( $id );
			if ( $tid ) {
				$thumb = $tid;
				break;
			}
		}
	}
	return array(
		'name'  => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
		'url'   => $link,
		'thumb' => $thumb,
	);
}

/**
 * @param int    $attachment_id Attachment ID.
 * @param string $alt           Alt text.
 * @param string $class         CSS class.
 * @param string $loading       eager or lazy.
 * @return string
 */
function sa_solution_img( $attachment_id, $alt, $class, $loading = 'lazy' ) {
	if ( ! $attachment_id ) {
		return '';
	}
	return wp_get_attachment_image(
		$attachment_id,
		'large',
		false,
		array(
			'class'    => $class,
			'alt'      => $alt,
			'loading'  => $loading,
			'decoding' => 'async',
			'sizes'    => '(max-width: 699px) 100vw, (max-width: 1024px) 50vw, 560px',
		)
	);
}

/**
 * @return array{contact:string,shop:string,hotline:string,tel:string}
 */
function sa_solution_links() {
	return array(
		'contact' => home_url( '/lien-he/' ),
		'shop'    => home_url( '/san-pham/' ),
		'hotline' => sa_business( 'hotline' ),
		'tel'     => preg_replace( '/\D+/', '', sa_business( 'hotline_tel' ) ),
	);
}

/**
 * @param string $title   CTA heading.
 * @param array  $links   Shared links.
 * @param string $primary Button label.
 * @return string
 */
function sa_solution_cta( $title, $links, $primary ) {
	ob_start();
	?>
	<section class="sa-sol-cta" aria-labelledby="sa-sol-cta-title">
		<div class="sa-sol-wrap">
			<h2 id="sa-sol-cta-title"><?php echo esc_html( $title ); ?></h2>
			<div class="sa-sol-cta__row">
				<a class="sa-sol-btn sa-sol-btn--solid" href="<?php echo esc_url( $links['contact'] ); ?>"><?php echo esc_html( $primary ); ?></a>
				<?php if ( $links['hotline'] && $links['tel'] ) : ?>
					<p class="sa-sol-cta__phone">Hotline: <a href="<?php echo esc_url( 'tel:' . $links['tel'] ); ?>"><?php echo esc_html( $links['hotline'] ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Enterprise solution page.
 *
 * @return string
 */
function sa_solution_enterprise_shortcode() {
	$links = sa_solution_links();
	$ups   = sa_solution_cat( 'bo-luu-dien-hyundai' );
	$hero  = sa_solution_cat( 'bo-luu-dien-ups-online' );
	$hero_id = $hero && $hero['thumb'] ? $hero['thumb'] : 51;

	$online  = sa_solution_cat( 'bo-luu-dien-ups-online' );
	$offline = sa_solution_cat( 'bo-luu-dien-offline' );
	$rack    = sa_solution_cat( 'bo-luu-dien-rack' );
	$pin     = sa_solution_cat( 'pin-ac-quy' );

	$problems = array(
		array(
			'title' => 'Mất điện đột ngột',
			'text'  => 'Điện lưới có thể mất giữa giờ làm việc. Máy tính, tổng đài và thiết bị bán hàng dừng cùng lúc nếu không có lớp nguồn dự phòng.',
		),
		array(
			'title' => 'Gián đoạn server và CNTT',
			'text'  => 'Máy chủ, bộ lưu trữ và thiết bị mạng cần điện liên tục trong lúc chuyển nguồn hoặc tắt có kiểm soát.',
		),
		array(
			'title' => 'Thiết bị quan trọng mất nguồn',
			'text'  => 'Một số tải không chấp nhận khoảng ngắt dù rất ngắn. UPS đứng trước những thiết bị đó, máy phát đảm nhiệm thời gian chạy dài hơn.',
		),
		array(
			'title' => 'Rủi ro mất dữ liệu và gián đoạn vận hành',
			'text'  => 'Mất điện giữa lúc ghi dữ liệu hoặc giao dịch làm dở quy trình. Nguồn dự phòng giữ cho thao tác đang chạy được kết thúc đúng cách.',
		),
	);

	$uses = array(
		'Văn phòng'     => 'Máy trạm, tổng đài, phòng họp và mạng nội bộ.',
		'Server'        => 'Tủ thiết bị và máy chủ cần thời gian lưu điện xác định.',
		'Data Center'   => 'Phòng máy với tải tập trung, ưu tiên UPS online.',
		'Cửa hàng'      => 'Thu ngân, camera và chiếu sáng khi lưới mất.',
		'Tòa nhà'       => 'Tải dùng chung như chiếu sáng kỹ thuật và thang máy, sau khi đã liệt kê phụ tải.',
		'Y tế'          => 'Thiết bị không được ngắt nguồn giữa quy trình.',
		'Giáo dục'      => 'Phòng máy, phòng thí nghiệm và hệ thống mạng của cơ sở.',
	);

	$steps = array(
		array( 'Khảo sát nhu cầu', 'Ghi nhận loại hình vận hành, vị trí lắp đặt và mức gián đoạn có thể chấp nhận.' ),
		array( 'Phân tích phụ tải', 'Tách tải liên tục và tải khởi động, xác định số pha, điện áp và thời gian cần chạy.' ),
		array( 'Chọn giải pháp', 'Đối chiếu UPS, máy phát hoặc ắc quy với model đang có trong danh mục.' ),
		array( 'Tư vấn triển khai', 'Làm rõ cách bố trí nguồn và chuyển mạch trước khi đưa vào vận hành.' ),
	);

	ob_start();
	?>
	<article class="sa-sol">
		<section class="sa-sol-hero" aria-labelledby="sa-sol-title">
			<div class="sa-sol-hero__inner">
				<div class="sa-sol-hero__copy">
					<p class="sa-sol-eyebrow">Thịnh Phát · Giải pháp doanh nghiệp</p>
					<h1 id="sa-sol-title">Giải pháp nguồn điện ổn định cho doanh nghiệp</h1>
					<p class="sa-sol-lead">Giải pháp UPS, máy phát điện và nguồn dự phòng giúp văn phòng, trung tâm dữ liệu, cửa hàng và hệ thống CNTT duy trì hoạt động khi nguồn điện gặp sự cố.</p>
					<div class="sa-sol-actions">
						<a class="sa-sol-btn sa-sol-btn--solid" href="<?php echo esc_url( $links['contact'] ); ?>">Nhận tư vấn</a>
						<a class="sa-sol-btn sa-sol-btn--ghost" href="<?php echo esc_url( $links['shop'] ); ?>">Xem sản phẩm</a>
					</div>
					<?php if ( $links['hotline'] && $links['tel'] ) : ?>
						<p class="sa-sol-hotline">Hotline: <a href="<?php echo esc_url( 'tel:' . $links['tel'] ); ?>"><?php echo esc_html( $links['hotline'] ); ?></a></p>
					<?php endif; ?>
				</div>
				<figure class="sa-sol-hero__media">
					<?php echo sa_solution_img( $hero_id, 'Bộ lưu điện dùng trong giải pháp nguồn dự phòng cho doanh nghiệp', 'sa-sol-hero__img', 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-band--light sa-sol-reveal" aria-labelledby="sa-sol-problems">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Vận hành</p>
					<h2 id="sa-sol-problems">Vấn đề doanh nghiệp thường gặp</h2>
					<p>Sự cố nguồn điện ít khi báo trước. Phần việc của giải pháp là giữ cho phần việc đang chạy không đứt giữa chừng.</p>
				</header>
				<div class="sa-sol-grid sa-sol-grid--4">
					<?php foreach ( $problems as $i => $item ) : ?>
						<article class="sa-sol-panel">
							<span class="sa-sol-index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<h3><?php echo esc_html( $item['title'] ); ?></h3>
							<p><?php echo esc_html( $item['text'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-reveal" aria-labelledby="sa-sol-offer">
			<div class="sa-sol-wrap sa-sol-split">
				<div>
					<header class="sa-sol-head">
						<p class="sa-sol-eyebrow">Thiết bị</p>
						<h2 id="sa-sol-offer">Giải pháp Thịnh Phát</h2>
						<p>Mỗi nhóm dưới đây là một cách giữ nguồn. Chọn theo phụ tải, không theo một model có sẵn.</p>
					</header>
					<div class="sa-sol-offers">
						<article>
							<h3>UPS Online, Offline và Rack</h3>
							<p>Online cho tải không được ngắt. Offline cho tải ít nhạy hơn. Rack khi thiết bị nằm trong tủ máy.</p>
							<p class="sa-sol-links">
								<?php if ( $online ) : ?><a href="<?php echo esc_url( $online['url'] ); ?>">UPS Online</a><?php endif; ?>
								<?php if ( $offline ) : ?><a href="<?php echo esc_url( $offline['url'] ); ?>">UPS Offline</a><?php endif; ?>
								<?php if ( $rack ) : ?><a href="<?php echo esc_url( $rack['url'] ); ?>">UPS Rack</a><?php endif; ?>
								<?php if ( $ups ) : ?><a href="<?php echo esc_url( $ups['url'] ); ?>">Xem bộ lưu điện</a><?php endif; ?>
							</p>
						</article>
						<article>
							<h3>Máy phát điện dự phòng</h3>
							<p>Dùng khi thời gian mất điện dài hơn khả năng lưu điện của UPS. Công suất lấy từ danh sách tải đã chốt.</p>
							<p class="sa-sol-links"><a href="<?php echo esc_url( $links['shop'] ); ?>">Xem máy phát điện</a></p>
						</article>
						<article>
							<h3>Pin và ắc quy</h3>
							<p>Nguồn lưu trữ đi cùng UPS khi cần kéo dài thời gian chạy trước khi máy phát vào tải hoặc trước khi tắt thiết bị.</p>
							<?php if ( $pin ) : ?>
								<p class="sa-sol-links"><a href="<?php echo esc_url( $pin['url'] ); ?>"><?php echo esc_html( $pin['name'] ); ?></a></p>
							<?php endif; ?>
						</article>
						<article>
							<h3>Giải pháp nguồn điện phù hợp từng phụ tải</h3>
							<p>Công suất liên tục, dòng khởi động, số pha và thời gian chạy quyết định cấu hình. Số liệu chưa có thì để trống, không suy đoán.</p>
							<p class="sa-sol-links"><a href="<?php echo esc_url( $links['contact'] ); ?>">Nhận tư vấn theo phụ tải</a></p>
						</article>
					</div>
				</div>
				<figure class="sa-sol-aside">
					<?php
					$aside = $ups && $ups['thumb'] ? $ups['thumb'] : $hero_id;
					echo sa_solution_img( $aside, 'Bộ lưu điện trong danh mục Thịnh Phát', 'sa-sol-aside__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</figure>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-band--light sa-sol-reveal" aria-labelledby="sa-sol-uses">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Phạm vi</p>
					<h2 id="sa-sol-uses">Ứng dụng</h2>
					<p>Cùng một nguyên tắc chọn nguồn, áp cho những nơi mất điện là gián đoạn công việc.</p>
				</header>
				<ul class="sa-sol-uses">
					<?php foreach ( $uses as $name => $text ) : ?>
						<li>
							<h3><?php echo esc_html( $name ); ?></h3>
							<p><?php echo esc_html( $text ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-reveal" aria-labelledby="sa-sol-process">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Cách làm</p>
					<h2 id="sa-sol-process">Quy trình</h2>
				</header>
				<ol class="sa-sol-steps">
					<?php foreach ( $steps as $i => $step ) : ?>
						<li>
							<span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<h3><?php echo esc_html( $step[0] ); ?></h3>
							<p><?php echo esc_html( $step[1] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
				<p class="sa-sol-cross"><a href="<?php echo esc_url( home_url( '/giai-phap/giai-phap-cong-nghiep/' ) ); ?>">Xem giải pháp cho nhà máy và dây chuyền</a></p>
			</div>
		</section>

		<?php echo sa_solution_cta( 'Đừng để sự cố nguồn điện làm gián đoạn hoạt động doanh nghiệp.', $links, 'NHẬN TƯ VẤN →' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_solution_enterprise', 'sa_solution_enterprise_shortcode' );

/**
 * Industrial solution page.
 *
 * @return string
 */
function sa_solution_industrial_shortcode() {
	$links = sa_solution_links();
	$hero  = sa_solution_cat( 'may-phat-dien-cummins' );
	if ( ! $hero || ! $hero['thumb'] ) {
		$hero = sa_solution_cat( 'may-phat-dien-hyundai' );
	}
	$hero_id = $hero && $hero['thumb'] ? $hero['thumb'] : 51;

	$ups_ind = sa_solution_cat( 'bo-luu-dien-cong-nghiep' );
	$inverter = sa_solution_cat( 'bien-tan' );
	$pin      = sa_solution_cat( 'pin-ac-quy' );

	$challenges = array(
		array(
			'title' => 'Phụ tải công suất lớn',
			'text'  => 'Động cơ và cụm máy kéo dòng khởi động cao hơn công suất chạy ổn định. Chọn máy theo cả hai mức, không chỉ theo số ghi trên catalog.',
		),
		array(
			'title' => 'Vận hành liên tục',
			'text'  => 'Ca sản xuất cần nguồn chạy dài. UPS giữ khoảng chuyển mạch. Máy phát nhận tải khi thời gian mất điện vượt quá thời gian lưu điện.',
		),
		array(
			'title' => 'Mất điện gây dừng dây chuyền',
			'text'  => 'Dừng giữa chu trình làm phát sinh phế phẩm và thời gian khởi động lại. Nguồn dự phòng được tính trên đúng cụm máy phải chạy tiếp.',
		),
		array(
			'title' => 'Thiết bị nhạy cảm với chất lượng nguồn',
			'text'  => 'Biến tần, bộ điều khiển và cảm biến chịu ảnh hưởng khi điện áp hoặc tần số lệch. UPS online cách ly nhóm thiết bị này khỏi nhiễu lưới.',
		),
	);

	$uses = array(
		'Nhà máy'            => 'Nguồn cho khu sản xuất và phòng điều khiển.',
		'Xưởng sản xuất'     => 'Máy công cụ và dây chuyền cỡ vừa.',
		'Kho vận'            => 'Băng tải, cửa và hệ thống quản lý kho.',
		'Công trình'         => 'Nguồn tạm khi điện lưới chưa ổn định.',
		'Hệ thống kỹ thuật'  => 'Tủ điện, bơm và thiết bị đo cần nguồn xác định.',
		'Hạ tầng'            => 'Trạm và cụm thiết bị phải chạy khi lưới sự cố.',
	);

	$steps = array(
		array( 'Xác định phụ tải', 'Liệt kê thiết bị phải chạy tiếp, thiết bị được phép dừng và thiết bị không được ngắt.' ),
		array( 'Tính công suất', 'Cộng tải liên tục, xét dòng khởi động, số pha và thời gian chạy.' ),
		array( 'Chọn thiết bị', 'Đối chiếu với máy phát, UPS, biến tần và ắc quy đang có trong danh mục.' ),
		array( 'Tư vấn triển khai', 'Bố trí máy, nhiên liệu, thông gió, chuyển mạch và điểm đấu nối.' ),
		array( 'Hỗ trợ vận hành', 'Làm rõ cách khởi động, chuyển nguồn và những việc cần kiểm tra định kỳ.' ),
	);

	$brand_slugs = array(
		'may-phat-dien-hyundai'    => 'Hyundai',
		'may-phat-dien-cummins'    => 'Cummins',
		'may-phat-dien-mitsubishi' => 'Mitsubishi',
		'may-phat-dien-kubota'     => 'Kubota',
		'may-phat-dien-baudouin'   => 'Baudouin',
	);
	$brands = array();
	foreach ( $brand_slugs as $slug => $label ) {
		$cat = sa_solution_cat( $slug );
		if ( $cat ) {
			$cat['label'] = $label;
			$brands[]     = $cat;
		}
	}

	ob_start();
	?>
	<article class="sa-sol">
		<section class="sa-sol-hero" aria-labelledby="sa-sol-title">
			<div class="sa-sol-hero__inner">
				<div class="sa-sol-hero__copy">
					<p class="sa-sol-eyebrow">Thịnh Phát · Giải pháp công nghiệp</p>
					<h1 id="sa-sol-title">Giải pháp nguồn điện cho hệ thống công nghiệp</h1>
					<p class="sa-sol-lead">Máy phát điện, UPS công nghiệp và thiết bị nguồn phục vụ nhà máy, dây chuyền sản xuất và các hệ thống kỹ thuật yêu cầu nguồn điện ổn định.</p>
					<div class="sa-sol-actions">
						<a class="sa-sol-btn sa-sol-btn--solid" href="<?php echo esc_url( $links['contact'] ); ?>">Tư vấn giải pháp</a>
						<a class="sa-sol-btn sa-sol-btn--ghost" href="<?php echo esc_url( $links['shop'] ); ?>">Xem máy phát điện</a>
					</div>
					<?php if ( $links['hotline'] && $links['tel'] ) : ?>
						<p class="sa-sol-hotline">Hotline: <a href="<?php echo esc_url( 'tel:' . $links['tel'] ); ?>"><?php echo esc_html( $links['hotline'] ); ?></a></p>
					<?php endif; ?>
				</div>
				<figure class="sa-sol-hero__media">
					<?php echo sa_solution_img( $hero_id, 'Máy phát điện công nghiệp trong danh mục Thịnh Phát', 'sa-sol-hero__img', 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-band--light sa-sol-reveal" aria-labelledby="sa-sol-challenges">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Hiện trường</p>
					<h2 id="sa-sol-challenges">Thách thức trong môi trường công nghiệp</h2>
					<p>Nguồn cho sản xuất khác nguồn cho văn phòng ở chỗ dòng khởi động lớn và thời gian phải chạy tiếp.</p>
				</header>
				<div class="sa-sol-grid sa-sol-grid--4">
					<?php foreach ( $challenges as $i => $item ) : ?>
						<article class="sa-sol-panel">
							<span class="sa-sol-index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<h3><?php echo esc_html( $item['title'] ); ?></h3>
							<p><?php echo esc_html( $item['text'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-reveal" aria-labelledby="sa-sol-eco">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Thiết bị</p>
					<h2 id="sa-sol-eco">Hệ sinh thái giải pháp</h2>
					<p>Các nhóm đang có trên website. Bấm vào tên để mở đúng danh mục sản phẩm.</p>
				</header>
				<div class="sa-sol-eco">
					<article>
						<h3>Máy phát điện công nghiệp</h3>
						<p>Nguồn chạy độc lập cho tải lớn và thời gian vận hành dài.</p>
						<p class="sa-sol-links"><a href="#may-phat-dien">Xem các dòng máy phát</a></p>
					</article>
					<article>
						<h3>UPS công nghiệp</h3>
						<p>Giữ điện cho tủ điều khiển và thiết bị không được rớt trong lúc chuyển nguồn.</p>
						<?php if ( $ups_ind ) : ?>
							<p class="sa-sol-links"><a href="<?php echo esc_url( $ups_ind['url'] ); ?>"><?php echo esc_html( $ups_ind['name'] ); ?></a></p>
						<?php endif; ?>
					</article>
					<article>
						<h3>Biến tần</h3>
						<p>Điều khiển động cơ khi hệ thống cần phối hợp tốc độ với nguồn.</p>
						<?php if ( $inverter ) : ?>
							<p class="sa-sol-links"><a href="<?php echo esc_url( $inverter['url'] ); ?>"><?php echo esc_html( $inverter['name'] ); ?></a></p>
						<?php endif; ?>
					</article>
					<article>
						<h3>Pin và ắc quy</h3>
						<p>Kéo dài thời gian lưu điện của UPS hoặc cụm nguồn lưu trữ.</p>
						<?php if ( $pin ) : ?>
							<p class="sa-sol-links"><a href="<?php echo esc_url( $pin['url'] ); ?>"><?php echo esc_html( $pin['name'] ); ?></a></p>
						<?php endif; ?>
					</article>
					<article>
						<h3>Thiết bị điện liên quan</h3>
						<p>Phần còn lại của danh mục nguồn điện đang đăng trên website.</p>
						<p class="sa-sol-links"><a href="<?php echo esc_url( $links['shop'] ); ?>">Xem toàn bộ sản phẩm</a></p>
					</article>
				</div>
			</div>
		</section>

		<?php if ( $brands ) : ?>
			<section class="sa-sol-band sa-sol-band--light sa-sol-reveal" id="may-phat-dien" aria-labelledby="sa-sol-brands">
				<div class="sa-sol-wrap">
					<header class="sa-sol-head">
						<p class="sa-sol-eyebrow">Danh mục đang có</p>
						<h2 id="sa-sol-brands">Máy phát điện</h2>
						<p>Hyundai, Cummins, Mitsubishi, Kubota và Baudouin dưới đây là các danh mục đang có sản phẩm. Ảnh lấy từ thư viện của website.</p>
					</header>
					<ul class="sa-sol-brands">
						<?php foreach ( $brands as $brand ) : ?>
							<li>
								<a class="sa-sol-brand" href="<?php echo esc_url( $brand['url'] ); ?>">
									<span class="sa-sol-brand__media">
										<?php echo sa_solution_img( $brand['thumb'], $brand['name'], 'sa-sol-brand__img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
									<span class="sa-sol-brand__name"><?php echo esc_html( $brand['label'] ); ?></span>
									<span class="sa-sol-brand__cat"><?php echo esc_html( $brand['name'] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<section class="sa-sol-band sa-sol-reveal" aria-labelledby="sa-sol-uses">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Phạm vi</p>
					<h2 id="sa-sol-uses">Ứng dụng</h2>
				</header>
				<ul class="sa-sol-uses sa-sol-uses--dark">
					<?php foreach ( $uses as $name => $text ) : ?>
						<li>
							<h3><?php echo esc_html( $name ); ?></h3>
							<p><?php echo esc_html( $text ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<section class="sa-sol-band sa-sol-band--light sa-sol-reveal" aria-labelledby="sa-sol-process">
			<div class="sa-sol-wrap">
				<header class="sa-sol-head">
					<p class="sa-sol-eyebrow">Cách làm</p>
					<h2 id="sa-sol-process">Quy trình giải pháp</h2>
				</header>
				<ol class="sa-sol-steps sa-sol-steps--five">
					<?php foreach ( $steps as $i => $step ) : ?>
						<li>
							<span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<h3><?php echo esc_html( $step[0] ); ?></h3>
							<p><?php echo esc_html( $step[1] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
				<p class="sa-sol-cross"><a href="<?php echo esc_url( home_url( '/giai-phap/giai-phap-doanh-nghiep/' ) ); ?>">Xem giải pháp cho văn phòng và hệ thống CNTT</a></p>
			</div>
		</section>

		<?php echo sa_solution_cta( 'Nguồn điện ổn định cho hoạt động sản xuất liên tục.', $links, 'NHẬN TƯ VẤN →' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</article>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_solution_industrial', 'sa_solution_industrial_shortcode' );

/**
 * @return bool
 */
function sa_solution_is_view() {
	return is_page( array( 'giai-phap-doanh-nghiep', 'giai-phap-cong-nghiep' ) );
}

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function sa_solution_body_class( $classes ) {
	if ( sa_solution_is_view() ) {
		$classes[] = 'sa-solution';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_solution_body_class' );

/**
 * CSS and the small reveal script, only on these two pages.
 */
function sa_solution_assets() {
	if ( ! sa_solution_is_view() ) {
		return;
	}
	$js = get_stylesheet_directory() . '/assets/js/solutions.js';
	if ( file_exists( $js ) ) {
		wp_enqueue_script( 'sa-solutions', get_stylesheet_directory_uri() . '/assets/js/solutions.js', array(), (string) filemtime( $js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'sa_solution_assets', 25 );

/**
 * @param array $parts Title parts.
 * @return array
 */
function sa_solution_document_title( $parts ) {
	if ( is_page( 'giai-phap-doanh-nghiep' ) ) {
		$parts['title'] = 'Giải pháp nguồn điện ổn định cho doanh nghiệp';
	} elseif ( is_page( 'giai-phap-cong-nghiep' ) ) {
		$parts['title'] = 'Giải pháp nguồn điện cho hệ thống công nghiệp';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sa_solution_document_title' );

/**
 * One meta description per page.
 */
function sa_solution_meta_description() {
	if ( is_page( 'giai-phap-doanh-nghiep' ) ) {
		$description = 'UPS, biến tần, pin và ắc quy cho văn phòng, cửa hàng, hệ thống CNTT và trung tâm dữ liệu. Thịnh Phát tư vấn theo phụ tải thực tế.';
	} elseif ( is_page( 'giai-phap-cong-nghiep' ) ) {
		$description = 'UPS công nghiệp, biến tần, pin và ắc quy cho nhà máy, dây chuyền và hệ thống kỹ thuật. Thịnh Phát tư vấn theo phụ tải thực tế.';
	} else {
		return;
	}
	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}
add_action( 'wp_head', 'sa_solution_meta_description', 1 );
