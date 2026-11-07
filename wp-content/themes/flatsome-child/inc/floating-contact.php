<?php
/**
 * Right floating contact bar. Contact details come from sa_get_business_info().
 */

defined( 'ABSPATH' ) || exit;

/**
 * Let iPhone home-indicator padding apply to the bottom bar.
 *
 * @param string $meta Viewport tag.
 * @return string
 */
function sa_float_viewport_meta( $meta ) {
	return '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />';
}
add_filter( 'flatsome_viewport_meta', 'sa_float_viewport_meta' );

/**
 * Mark pages that render the bar so compact layouts can pad the page end.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function sa_float_body_class( $classes ) {
	if ( ! is_admin() && sa_float_phone_digits() && sa_business( 'facebook_url' ) ) {
		$classes[] = 'sa-has-float';
	}
	return $classes;
}
add_filter( 'body_class', 'sa_float_body_class' );

/**
 * Digits-only phone from the single hotline field.
 *
 * @return string
 */
function sa_float_phone_digits() {
	return preg_replace( '/\D+/', '', sa_business( 'hotline_tel' ) );
}

/**
 * Future seoauto.vn chatbot mount. Disabled until a script URL is provided.
 *
 * @return array{enabled:bool,src:string,mount:string}
 */
function sa_seoauto_chatbot_config() {
	$config = array(
		'enabled' => false,
		'src'     => '',
		'mount'   => 'sa-seoauto-chatbot',
	);
	$filtered = apply_filters( 'sa_seoauto_chatbot_config', $config );
	return is_array( $filtered ) ? array_merge( $config, $filtered ) : $config;
}

/**
 * Create the Contact Form 7 form once. Mail recipient is the shared inbox.
 *
 * @return int
 */
function sa_float_ensure_cf7_form() {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return 0;
	}
	$stored = (int) get_option( 'sa_cf7_float_form_id' );
	if ( $stored && 'wpcf7_contact_form' === get_post_type( $stored ) ) {
		return $stored;
	}

	$inbox = sa_contact_inbox_email();
	$form  = WPCF7_ContactForm::get_template(
		array(
			'title' => 'Thanh liên hệ',
		)
	);
	$form->set_properties(
		array(
			'form' => implode(
				"\n",
				array(
					'<label>Họ và tên *',
					'    [text* your-name autocomplete:name maxlength:120]</label>',
					'<label>Email *',
					'    [email* your-email autocomplete:email maxlength:120]</label>',
					'<label>Số điện thoại *',
					'    [tel* your-phone autocomplete:tel minlength:8 maxlength:20]</label>',
					'<label>Nội dung *',
					'    [textarea* your-message maxlength:4000]</label>',
					'<p class="sa-hp" aria-hidden="true">[text sa-hp]</p>',
					'[submit "GỬI LIÊN HỆ"]',
				)
			),
			'mail' => array(
				'active'             => true,
				'subject'            => '[THỊNH PHÁT] Liên hệ từ [your-name]',
				'sender'             => sprintf( 'THỊNH PHÁT <%s>', $inbox ? $inbox : get_option( 'admin_email' ) ),
				'recipient'          => $inbox ? $inbox : get_option( 'admin_email' ),
				'body'               => "Họ và tên: [your-name]\nEmail: [your-email]\nSố điện thoại: [your-phone]\n\nNội dung:\n[your-message]",
				'additional_headers' => 'Reply-To: [your-email]',
				'attachments'        => '',
				'use_html'           => false,
				'exclude_blank'      => false,
			),
			'mail_2'     => array( 'active' => false ),
			'messages'   => $form->get_properties()['messages'],
			'additional_settings' => '',
		)
	);
	$form->save();
	update_option( 'sa_cf7_float_form_id', (int) $form->id(), false );
	return (int) $form->id();
}
add_action( 'init', 'sa_float_ensure_cf7_form', 20 );

/**
 * Reject the honeypot when it is filled in.
 *
 * @param bool $spam Spam flag.
 * @return bool
 */
function sa_float_cf7_honeypot( $spam ) {
	if ( $spam ) {
		return $spam;
	}
	$submission = class_exists( 'WPCF7_Submission' ) ? WPCF7_Submission::get_instance() : null;
	if ( ! $submission ) {
		return $spam;
	}
	$trap = $submission->get_posted_data( 'sa-hp' );
	if ( is_string( $trap ) && '' !== trim( $trap ) ) {
		return true;
	}
	return $spam;
}
add_filter( 'wpcf7_spam', 'sa_float_cf7_honeypot' );

/**
 * Keep the CF7 recipient on the shared inbox even if the form was saved earlier.
 *
 * @param array $components Mail components.
 * @return array
 */
function sa_float_cf7_recipient( $components ) {
	$inbox = sa_contact_inbox_email();
	if ( $inbox ) {
		$components['recipient'] = $inbox;
	}
	return $components;
}
add_filter( 'wpcf7_mail_components', 'sa_float_cf7_recipient' );

/**
 * Print the bar and the slide-out contact form.
 */
function sa_float_render() {
	if ( is_admin() ) {
		return;
	}
	$phone    = sa_float_phone_digits();
	$display  = sa_business( 'hotline' );
	$facebook = sa_business( 'facebook_url' );
	$chatbot  = sa_seoauto_chatbot_config();
	$form_id  = sa_float_ensure_cf7_form();
	if ( ! $phone || ! $facebook ) {
		return;
	}
	$zalo = 'https://zalo.me/' . rawurlencode( $phone );
	$tel  = 'tel:' . $phone;
	?>
	<aside class="sa-float" data-sa-float>
		<div class="sa-float__stack">
			<div class="sa-float__contact" data-sa-contact-zone>
				<div class="sa-float__sheet" id="sa-float-sheet" data-sa-contact-sheet role="dialog" aria-modal="false" aria-hidden="true" aria-labelledby="sa-float-sheet-title">
					<button type="button" class="sa-float__sheet-close" data-sa-close-contact aria-label="Đóng">×</button>
					<h2 id="sa-float-sheet-title">Liên hệ</h2>
					<?php
					if ( $form_id && function_exists( 'wpcf7_contact_form' ) ) {
						echo do_shortcode( '[contact-form-7 id="' . (int) $form_id . '"]' );
					} else {
						echo '<p>Form liên hệ chưa sẵn sàng.</p>';
					}
					?>
				</div>
				<button type="button" class="sa-float__tab" data-sa-open-contact aria-expanded="false" aria-controls="sa-float-sheet">Liên hệ</button>
			</div>
			<a class="sa-float__item sa-float__item--msg" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="sa-float__pill">
					<span class="sa-float__label">Chat với chúng tôi</span>
					<span class="sa-float__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 3C6.5 3 2 6.9 2 11.7c0 2.7 1.4 5.1 3.6 6.7V21l3.3-1.8c1 .3 2 .4 3.1.4 5.5 0 10-3.9 10-8.7S17.5 3 12 3zm1 11.7-2.5-2.7-4.9 2.7 5.4-5.7 2.6 2.7 4.8-2.7-5.4 5.7z"/></svg>
					</span>
				</span>
				<span class="sa-float__short">Messenger</span>
				<span class="screen-reader-text">Messenger</span>
			</a>
			<a class="sa-float__item sa-float__item--zalo" href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="sa-float__pill">
					<span class="sa-float__label">Zalo: <?php echo esc_html( $display ); ?></span>
					<span class="sa-float__icon" aria-hidden="true">Z</span>
				</span>
				<span class="sa-float__short">Zalo</span>
				<span class="screen-reader-text">Zalo</span>
			</a>
			<a class="sa-float__item sa-float__item--phone" href="<?php echo esc_url( $tel ); ?>">
				<span class="sa-float__pill">
					<span class="sa-float__label">Hotline: <?php echo esc_html( $display ); ?></span>
					<span class="sa-float__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M6.6 3.8c.4-.4 1-.5 1.5-.2l2.2 1.3c.5.3.7.9.6 1.4l-.6 2.2c-.1.4 0 .9.3 1.2l2.2 2.2c.3.3.8.4 1.2.3l2.2-.6c.5-.1 1.1.1 1.4.6l1.3 2.2c.3.5.2 1.1-.2 1.5l-1.4 1.4c-.4.4-1 .6-1.6.5C10.2 17.4 6.6 13.8 5.7 8.8c-.1-.6.1-1.2.5-1.6l1.4-1.4z"/></svg>
					</span>
				</span>
				<span class="sa-float__short">Gọi</span>
				<span class="screen-reader-text">Điện thoại</span>
			</a>
		</div>
		<button type="button" class="sa-float__bot" data-sa-chatbot aria-controls="<?php echo esc_attr( $chatbot['mount'] ); ?>" aria-expanded="false">
			<span>Chatbot AI</span>
		</button>
		<div id="<?php echo esc_attr( $chatbot['mount'] ); ?>" class="sa-float__bot-panel" hidden data-provider="seoauto.vn" data-enabled="<?php echo ! empty( $chatbot['enabled'] ) && ! empty( $chatbot['src'] ) ? '1' : '0'; ?>"<?php echo ! empty( $chatbot['src'] ) ? ' data-src="' . esc_url( $chatbot['src'] ) . '"' : ''; ?>></div>
	</aside>
	<?php
}
add_action( 'wp_footer', 'sa_float_render', 15 );

/**
 * Assets for the bar. Scoped stylesheet, loaded on every public page.
 */
function sa_float_assets() {
	if ( is_admin() ) {
		return;
	}
	$js      = '/assets/js/floating-contact.js';
	$js_path = get_stylesheet_directory() . $js;
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script( 'sa-floating-contact', get_stylesheet_directory_uri() . $js, array(), (string) filemtime( $js_path ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'sa_float_assets', 30 );
