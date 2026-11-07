<?php
/**
 * Phase 8 — WooCommerce product schema for THỊNH PHÁT.
 * Registers attributes + meta helpers. Does NOT create fake products.
 */

defined( 'ABSPATH' ) || exit;

/** Option flag: real catalog data still required. */
define( 'SA_NEED_PRODUCT_DATA', true );

/**
 * Canonical category map (term slug => label).
 *
 * @return array<string,string>
 */
function sa_product_category_map() {
	return array(
		'ups-offline'      => 'UPS Offline',
		'ups-online'       => 'UPS Online',
		'ups-online-rack'  => 'UPS Online Rack',
		'ups-cong-nghiep'  => 'UPS Công nghiệp',
		'bien-tan'         => 'Biến tần',
		'pin-ac-quy'       => 'Pin & Ắc quy',
	);
}

/**
 * Global attribute definitions (slug without pa_ prefix => label).
 *
 * @return array<string,string>
 */
function sa_product_attribute_defs() {
	return array(
		'thuong-hieu' => 'Thương hiệu',
		'cong-suat'   => 'Công suất',
		'dien-ap'     => 'Điện áp',
	);
}

/**
 * Product field schema (for imports / admin / docs).
 *
 * @return array<string,array<string,string>>
 */
function sa_product_field_schema() {
	return array(
		'name'             => array( 'type' => 'string', 'woo' => 'post_title', 'required' => '1' ),
		'image'            => array( 'type' => 'attachment', 'woo' => '_thumbnail_id', 'required' => '1' ),
		'categories'       => array( 'type' => 'product_cat', 'woo' => 'product_cat', 'required' => '1' ),
		'brand'            => array( 'type' => 'attribute', 'woo' => 'pa_thuong-hieu', 'required' => '1' ),
		'model_sku'        => array( 'type' => 'string', 'woo' => '_sku', 'required' => '1' ),
		'power'            => array( 'type' => 'attribute', 'woo' => 'pa_cong-suat', 'required' => '1' ),
		'voltage'          => array( 'type' => 'attribute', 'woo' => 'pa_dien-ap', 'required' => '1' ),
		'key_specs'        => array( 'type' => 'string', 'woo' => '_sa_key_specs', 'required' => '1' ),
		'short_description'=> array( 'type' => 'text', 'woo' => 'post_excerpt', 'required' => '1' ),
		'description'      => array( 'type' => 'html', 'woo' => 'post_content', 'required' => '1' ),
		'datasheet'        => array( 'type' => 'attachment', 'woo' => '_sa_datasheet_id', 'required' => '0' ),
		'price'            => array( 'type' => 'price_or_contact', 'woo' => '_regular_price|_sa_price_on_request', 'required' => '1' ),
		'cta'              => array( 'type' => 'link', 'woo' => 'consult', 'required' => '1', 'label' => 'NHẬN TƯ VẤN' ),
	);
}

/**
 * Ensure global Woo attributes exist (idempotent).
 */
function sa_register_product_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}

	$existing = array();
	foreach ( wc_get_attribute_taxonomies() as $tax ) {
		$existing[ $tax->attribute_name ] = (int) $tax->attribute_id;
	}

	foreach ( sa_product_attribute_defs() as $slug => $label ) {
		if ( isset( $existing[ $slug ] ) ) {
			continue;
		}

		$id = wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}

		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy, array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_' . $taxonomy,
					array(
						'labels'       => array( 'name' => $label ),
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				)
			);
		}
	}

	delete_transient( 'wc_attribute_taxonomies' );
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}
}
add_action( 'init', 'sa_register_product_attributes', 20 );

/**
 * Register product meta keys for REST / import readiness.
 */
function sa_register_product_meta() {
	$keys = array(
		'_sa_key_specs'        => 'string',
		'_sa_datasheet_id'     => 'integer',
		'_sa_price_on_request' => 'string',
	);

	foreach ( $keys as $key => $type ) {
		register_post_meta(
			'product',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}
}
add_action( 'init', 'sa_register_product_meta', 25 );

/**
 * Admin fields on product general tab (empty until real data is entered).
 */
function sa_product_admin_fields() {
	echo '<div class="options_group sa-product-schema-fields">';
	woocommerce_wp_textarea_input(
		array(
			'id'          => '_sa_key_specs',
			'label'       => __( 'Thông số chính', 'flatsome-child' ),
			'description' => __( 'Ví dụ: Online double-conversion · 0ms transfer · LCD', 'flatsome-child' ),
			'desc_tip'    => true,
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_sa_datasheet_id',
			'label'       => __( 'Datasheet (Attachment ID)', 'flatsome-child' ),
			'description' => __( 'ID file PDF trong Media Library. Để trống nếu chưa có.', 'flatsome-child' ),
			'desc_tip'    => true,
			'type'        => 'number',
		)
	);
	woocommerce_wp_checkbox(
		array(
			'id'          => '_sa_price_on_request',
			'label'       => __( 'Giá: Liên hệ', 'flatsome-child' ),
			'description' => __( 'Bật khi chưa công bố giá — frontend hiện “Liên hệ”.', 'flatsome-child' ),
		)
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'sa_product_admin_fields' );

/**
 * Save admin schema fields.
 *
 * @param int $post_id Product ID.
 */
function sa_product_admin_fields_save( $post_id ) {
	if ( isset( $_POST['_sa_key_specs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, '_sa_key_specs', sanitize_textarea_field( wp_unslash( $_POST['_sa_key_specs'] ) ) );
	}
	if ( isset( $_POST['_sa_datasheet_id'] ) ) {
		update_post_meta( $post_id, '_sa_datasheet_id', absint( $_POST['_sa_datasheet_id'] ) );
	}
	$on_request = isset( $_POST['_sa_price_on_request'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_sa_price_on_request', $on_request );
}
add_action( 'woocommerce_process_product_meta', 'sa_product_admin_fields_save' );

/**
 * @param WC_Product $product Product.
 * @return string
 */
function sa_product_key_specs( $product ) {
	$specs = (string) $product->get_meta( '_sa_key_specs', true );
	if ( '' !== $specs ) {
		return $specs;
	}

	$parts = array();
	foreach ( array( 'pa_cong-suat', 'pa_dien-ap', 'pa_thuong-hieu' ) as $tax ) {
		$vals = wc_get_product_terms( $product->get_id(), $tax, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $vals ) && ! empty( $vals ) ) {
			$parts[] = implode( ', ', $vals );
		}
	}
	return implode( ' · ', $parts );
}

/**
 * Display price or “Liên hệ”.
 *
 * @param WC_Product $product Product.
 * @return string HTML-safe plain text (escaped by caller for attr; use esc_html when print).
 */
function sa_product_price_label( $product ) {
	if ( 'yes' === $product->get_meta( '_sa_price_on_request', true ) ) {
		return 'Liên hệ';
	}

	$price = $product->get_price();
	if ( '' === $price || null === $price ) {
		return 'Liên hệ';
	}

	return wp_strip_all_tags( wc_price( $price ) );
}

/**
 * Consult CTA URL (homepage contact / tel).
 *
 * @return string
 */
function sa_product_consult_url() {
	$tel = sa_business( 'hotline_tel' );
	if ( $tel ) {
		return 'tel:' . preg_replace( '/\D+/', '', $tel );
	}
	return home_url( '/lien-he/' );
}

/**
 * Datasheet URL if attachment exists.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function sa_product_datasheet_url( $product ) {
	$id = absint( $product->get_meta( '_sa_datasheet_id', true ) );
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}
