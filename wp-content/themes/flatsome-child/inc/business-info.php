<?php
/**
 * Canonical business / brand info for THỊNH PHÁT.
 * Bank account is private and must never be printed on the frontend.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public business info.
 *
 * @return array<string,string>
 */
function sa_get_business_info() {
	$defaults = array(
		'brand'        => 'THỊNH PHÁT',
		'company_name' => 'CÔNG TY TNHH THIẾT BỊ ĐIỆN CÔNG NGHIỆP THỊNH PHÁT',
		'hotline'      => '0933 055 222',
		'hotline_tel'  => '0933055222',
		'facebook_url' => 'https://www.facebook.com/chien.tranngoc.108',
		'address'      => 'Số ¾ Đường 37, Phường Hiệp Bình, TP. Hồ Chí Minh, Việt Nam',
		'tax_id'       => '0319550463',
		'email'        => '', // optional inbox — do not invent; falls back to admin_email
	);

	$stored = get_option( 'sa_business_info', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_merge( $defaults, array_intersect_key( $stored, $defaults ) );
}

/**
 * @param string $key Info key.
 * @return string
 */
function sa_business( $key ) {
	$info = sa_get_business_info();
	return isset( $info[ $key ] ) ? (string) $info[ $key ] : '';
}
