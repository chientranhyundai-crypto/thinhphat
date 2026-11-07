<?php
/**
 * Phase 10 — Site footer (B2B) from sa_business_info.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable default Flatsome footer widget rows; we render a custom footer.
 */
function sa_footer_theme_mods() {
	set_theme_mod( 'footer_1', 0 );
	set_theme_mod( 'footer_2', 0 );
	set_theme_mod( 'footer_left_text', '© ' . gmdate( 'Y' ) . ' ' . sa_business( 'brand' ) );
	set_theme_mod( 'footer_right_text', '' );
}
add_action( 'after_setup_theme', 'sa_footer_theme_mods', 30 );

/**
 * Render custom footer before Flatsome absolute bar.
 */
function sa_render_site_footer() {
	$brand   = sa_business( 'brand' );
	$company = sa_business( 'company_name' );
	$address = sa_business( 'address' );
	$hotline = sa_business( 'hotline' );
	$tel     = sa_business( 'hotline_tel' );
	$tax     = sa_business( 'tax_id' );

	$cats = array(
		'ups-offline'     => 'UPS Offline',
		'ups-online'      => 'UPS Online',
		'ups-online-rack' => 'UPS Online Rack',
		'ups-cong-nghiep' => 'UPS Công nghiệp',
		'bien-tan'        => 'Biến tần',
		'pin-ac-quy'      => 'Pin & Ắc quy',
	);

	$quick = array(
		'/gioi-thieu/' => 'Giới thiệu',
		'/san-pham/'   => 'Sản phẩm',
		'/giai-phap/'  => 'Giải pháp',
		'/du-an/'      => 'Dự án',
		'/tin-tuc/'    => 'Tin tức',
		'/lien-he/'    => 'Liên hệ',
	);
	?>
	<div class="sa-site-footer" id="sa-site-footer">
		<div class="sa-site-footer__inner">
			<div class="sa-site-footer__grid">
				<details class="sa-footer-col sa-footer-col--company" open>
					<summary class="sa-footer-col__title"><?php echo esc_html( $brand ); ?></summary>
					<div class="sa-footer-col__body">
						<p class="sa-footer-col__company"><?php echo esc_html( $company ); ?></p>
						<?php if ( $address ) : ?>
							<p class="sa-footer-col__line"><span class="sa-footer-col__label">Địa chỉ</span><?php echo esc_html( $address ); ?></p>
						<?php endif; ?>
						<?php if ( $tax ) : ?>
							<p class="sa-footer-col__line"><span class="sa-footer-col__label">MST</span><?php echo esc_html( $tax ); ?></p>
						<?php endif; ?>
					</div>
				</details>

				<details class="sa-footer-col sa-footer-col--accordion">
					<summary class="sa-footer-col__title">Danh mục sản phẩm</summary>
					<div class="sa-footer-col__body">
						<ul class="sa-footer-col__list">
							<?php foreach ( $cats as $slug => $label ) : ?>
								<?php
								$term = get_term_by( 'slug', $slug, 'product_cat' );
								$url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/san-pham/' );
								if ( is_wp_error( $url ) ) {
									$url = home_url( '/san-pham/' );
								}
								?>
								<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</details>

				<details class="sa-footer-col sa-footer-col--accordion">
					<summary class="sa-footer-col__title">Liên kết nhanh</summary>
					<div class="sa-footer-col__body">
						<ul class="sa-footer-col__list">
							<?php foreach ( $quick as $path => $label ) : ?>
								<li><a href="<?php echo esc_url( home_url( $path ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</details>

				<details class="sa-footer-col sa-footer-col--accordion">
					<summary class="sa-footer-col__title">Liên hệ</summary>
					<div class="sa-footer-col__body">
						<?php if ( $hotline && $tel ) : ?>
							<p class="sa-footer-col__line">
								<span class="sa-footer-col__label">Hotline</span>
								<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\D+/', '', $tel ) ); ?>"><?php echo esc_html( $hotline ); ?></a>
							</p>
						<?php endif; ?>
						<p class="sa-footer-col__line">
							<a class="sa-footer-col__cta" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Gửi yêu cầu tư vấn</a>
						</p>
					</div>
				</details>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'flatsome_before_footer', 'sa_render_site_footer', 5 );

/**
 * Open footer accordion columns on desktop for accessibility.
 */
function sa_footer_accordion_script() {
	?>
	<script>
	(function () {
	  function syncFooterAccordion() {
	    var cols = document.querySelectorAll('.sa-site-footer .sa-footer-col--accordion');
	    if (!cols.length) return;
	    var desktop = window.matchMedia('(min-width: 900px)').matches;
	    cols.forEach(function (el) {
	      if (desktop) el.setAttribute('open', 'open');
	      else el.removeAttribute('open');
	    });
	  }
	  syncFooterAccordion();
	  window.addEventListener('resize', syncFooterAccordion);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'sa_footer_accordion_script', 40 );
