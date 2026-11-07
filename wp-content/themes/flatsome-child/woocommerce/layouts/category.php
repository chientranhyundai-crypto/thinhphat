<?php
/**
 * Shop archive layout — left filter column. Product loop stays the native Flatsome/Woo card.
 *
 * @package Flatsome Child
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="row category-page-row">

		<div class="col large-3 hide-for-medium sa-filter-col <?php flatsome_sidebar_classes(); ?>">
			<div id="shop-sidebar" class="sidebar-inner col-inner">
				<?php echo sa_product_filter_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<div class="col large-9">
		<?php
		do_action( 'woocommerce_before_main_content' );

		if ( fl_woocommerce_version_check( '8.8.0' ) ) {
			do_action( 'woocommerce_shop_loop_header' );
		} else {
			do_action( 'woocommerce_archive_description' );
		}

		echo sa_product_filter_toolbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo sa_product_filter_results_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'flatsome_products_after' );
		do_action( 'woocommerce_after_main_content' );
		?>
		</div>
</div>
<button type="button" class="sa-filter-backdrop" hidden aria-label="<?php esc_attr_e( 'Đóng bộ lọc', 'flatsome-child' ); ?>"></button>
<?php
