<?php
/**
 * Single project. Header, footer, and floating contact stay on the default hooks.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="content" class="content-area page-wrapper" role="main">
	<?php
	while ( have_posts() ) {
		the_post();
		echo sa_du_an_render_single( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
<?php
get_footer();
