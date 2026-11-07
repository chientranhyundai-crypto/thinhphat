<?php
/**
 * Phase 10 — Contact page helpers + inquiry form (no invented email).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contact inbox email from business info (optional).
 *
 * @return string
 */
function sa_contact_inbox_email() {
	$info = sa_get_business_info();
	$email = isset( $info['email'] ) ? trim( (string) $info['email'] ) : '';
	if ( $email && is_email( $email ) ) {
		return $email;
	}
	$admin = get_option( 'admin_email' );
	if ( is_string( $admin ) && is_email( $admin ) ) {
		return $admin;
	}
	return '';
}

/**
 * Register inquiry CPT for form storage when mail is unavailable.
 */
function sa_register_inquiry_cpt() {
	register_post_type(
		'sa_inquiry',
		array(
			'labels'              => array(
				'name'          => 'Yêu cầu liên hệ',
				'singular_name' => 'Yêu cầu liên hệ',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor' ),
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'sa_register_inquiry_cpt' );

/**
 * Handle contact form POST.
 */
function sa_handle_contact_form() {
	if ( empty( $_POST['sa_contact_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sa_contact_nonce'] ) ), 'sa_contact_submit' ) ) {
		return;
	}
	if ( ! empty( $_POST['sa_company_website'] ) ) { // honeypot
		wp_safe_redirect( add_query_arg( 'sa_sent', '1', wp_get_referer() ? wp_get_referer() : home_url( '/lien-he/' ) ) );
		exit;
	}

	$name    = isset( $_POST['sa_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_name'] ) ) : '';
	$phone   = isset( $_POST['sa_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_phone'] ) ) : '';
	$email   = isset( $_POST['sa_email'] ) ? sanitize_email( wp_unslash( $_POST['sa_email'] ) ) : '';
	$message = isset( $_POST['sa_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sa_message'] ) ) : '';

	$errors = array();
	if ( '' === $name ) {
		$errors[] = 'name';
	}
	if ( '' === $phone ) {
		$errors[] = 'phone';
	}
	if ( '' === $message ) {
		$errors[] = 'message';
	}
	if ( $email && ! is_email( $email ) ) {
		$errors[] = 'email';
	}

	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/lien-he/' );
	if ( $errors ) {
		wp_safe_redirect( add_query_arg( 'sa_err', '1', $redirect ) );
		exit;
	}

	$body = "Họ tên: {$name}\nĐiện thoại: {$phone}\nEmail: {$email}\n\n{$message}";
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'sa_inquiry',
			'post_status'  => 'private',
			'post_title'   => $name . ' — ' . $phone,
			'post_content' => $body,
		),
		true
	);

	$inbox = sa_contact_inbox_email();
	if ( $inbox && ! is_wp_error( $post_id ) ) {
		wp_mail(
			$inbox,
			'[THỊNH PHÁT] Yêu cầu tư vấn từ ' . $name,
			$body,
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	wp_safe_redirect( add_query_arg( 'sa_sent', '1', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_sa_contact_submit', 'sa_handle_contact_form' );
add_action( 'admin_post_sa_contact_submit', 'sa_handle_contact_form' );

/**
 * Form card. Nonce is created at render time, so this stays PHP.
 *
 * @return string
 */
function sa_contact_form_markup() {
	$sent = isset( $_GET['sa_sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$err  = isset( $_GET['sa_err'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	ob_start();
	?>
	<div class="sa-contact__form-wrap">
		<?php if ( $sent ) : ?>
			<div class="sa-contact__alert sa-contact__alert--ok" role="status">Đã nhận yêu cầu. Chúng tôi sẽ liên hệ lại sớm.</div>
		<?php elseif ( $err ) : ?>
			<div class="sa-contact__alert sa-contact__alert--err" role="alert">Vui lòng điền đầy đủ Họ tên, Điện thoại và Nội dung.</div>
		<?php endif; ?>
		<form class="sa-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="sa_contact_submit" />
			<?php wp_nonce_field( 'sa_contact_submit', 'sa_contact_nonce' ); ?>
			<p class="sa-contact-form__hp" aria-hidden="true">
				<label>Website<input type="text" name="sa_company_website" value="" tabindex="-1" autocomplete="off" /></label>
			</p>
			<label class="sa-contact-form__field">
				<span>Họ tên *</span>
				<input type="text" name="sa_name" required maxlength="120" autocomplete="name" />
			</label>
			<label class="sa-contact-form__field">
				<span>Điện thoại *</span>
				<input type="tel" name="sa_phone" required maxlength="40" autocomplete="tel" />
			</label>
			<label class="sa-contact-form__field">
				<span>Email</span>
				<input type="email" name="sa_email" maxlength="120" autocomplete="email" />
			</label>
			<label class="sa-contact-form__field">
				<span>Nội dung *</span>
				<textarea name="sa_message" rows="5" required maxlength="4000"></textarea>
			</label>
			<button class="sa-btn sa-btn--primary sa-contact-form__submit" type="submit">GỬI YÊU CẦU</button>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Shortcode: contact page content + form.
 *
 * part="form" renders only the inquiry form for the native page layout.
 * The default still renders the full page.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function sa_contact_page_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'part' => '',
		),
		is_array( $atts ) ? $atts : array(),
		'sa_contact_page'
	);
	if ( 'form' === $atts['part'] ) {
		return sa_contact_form_markup();
	}

	$brand   = sa_business( 'brand' );
	$company = sa_business( 'company_name' );
	$address = sa_business( 'address' );
	$hotline = sa_business( 'hotline' );
	$tel     = sa_business( 'hotline_tel' );
	$tax     = sa_business( 'tax_id' );
	$inbox   = sa_contact_inbox_email();

	ob_start();
	?>
	<div class="sa-contact">
		<div class="sa-contact__grid">
			<div class="sa-contact__info">
				<p class="sa-eyebrow"><?php echo esc_html( $brand ); ?></p>
				<h1 class="sa-contact__title">Liên hệ với chúng tôi</h1>
				<p class="sa-contact__company"><?php echo esc_html( $company ); ?></p>
				<ul class="sa-contact__list">
					<?php if ( $address ) : ?>
						<li><strong>Địa chỉ</strong><span><?php echo esc_html( $address ); ?></span></li>
					<?php endif; ?>
					<?php if ( $hotline && $tel ) : ?>
						<li><strong>Hotline</strong><span><a href="<?php echo esc_url( 'tel:' . preg_replace( '/\D+/', '', $tel ) ); ?>"><?php echo esc_html( $hotline ); ?></a></span></li>
					<?php endif; ?>
					<?php if ( $tax ) : ?>
						<li><strong>MST</strong><span><?php echo esc_html( $tax ); ?></span></li>
					<?php endif; ?>
				</ul>
				<?php if ( ! $inbox ) : ?>
					<p class="sa-contact__note">Yêu cầu gửi qua form sẽ được lưu trên hệ thống. Cấu hình email nhận form khi sẵn sàng.</p>
				<?php endif; ?>
			</div>
			<?php echo sa_contact_form_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'sa_contact_page', 'sa_contact_page_shortcode' );
