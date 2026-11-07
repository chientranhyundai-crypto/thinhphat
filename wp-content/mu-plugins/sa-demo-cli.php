<?php
/**
 * Plugin Name: SA Demo CLI bootstrap
 * Description: Registers wp sa remove-demo / seed-demo early for WP-CLI (demo catalog only).
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Load child-theme demo helpers then register commands after WP loads the theme.
 */
WP_CLI::add_hook(
	'before_wp_load',
	function () {
		// no-op placeholder — real registration after theme.
	}
);

add_action(
	'after_setup_theme',
	function () {
		if ( ! function_exists( 'sa_register_cli_commands' ) ) {
			$demo = get_stylesheet_directory() . '/inc/demo-data.php';
			if ( file_exists( $demo ) ) {
				require_once $demo;
			}
		}
		if ( function_exists( 'sa_register_cli_commands' ) ) {
			sa_register_cli_commands();
		}
	},
	20
);
