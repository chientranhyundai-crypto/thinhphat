<?php
/**
 * Plugin Name: Thịnh Phát Performance Optimizer
 * Description: Lightweight performance optimizations without altering site features or styling.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// 1. Disable XML-RPC (Security and performance)
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

// 2. Remove WordPress Emojis
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', function ( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	} );
	add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$emoji_svg_url = 'https://s.w.org/images/core/emoji/';
			foreach ( $urls as $key => $url ) {
				if ( is_string( $url ) && strpos( $url, $emoji_svg_url ) !== false ) {
					unset( $urls[ $key ] );
				}
			}
		}
		return $urls;
	}, 10, 2 );
} );

// 3. Remove WP Embed script
add_action( 'wp_footer', function () {
	wp_dequeue_script( 'wp-embed' );
} );

// 4. Optimize Heartbeat API (Reduce frequency to 60s in admin to lower server CPU load)
add_filter( 'heartbeat_settings', function ( $settings ) {
	$settings['interval'] = 60;
	return $settings;
} );

// 5. Optimize WooCommerce Cart Fragments when cart is empty
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_woocommerce' ) && function_exists( 'is_cart' ) && function_exists( 'is_checkout' ) ) {
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
			if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->is_empty() ) {
				wp_dequeue_script( 'wc-cart-fragments' );
			}
		}
	}
}, 99 );

