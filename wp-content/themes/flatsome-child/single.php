<?php
/**
 * Single blog post. Other post types keep the parent template.
 */

defined( 'ABSPATH' ) || exit;

if ( get_post_type() !== 'post' ) {
	include get_template_directory() . '/single.php';
	return;
}

get_header();
?>
<div id="content" class="content-area page-wrapper" role="main">
	<?php
	while ( have_posts() ) {
		the_post();
		echo sa_news_render_single( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
<?php
get_footer();
