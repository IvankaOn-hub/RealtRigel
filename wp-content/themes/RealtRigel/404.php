<?php
/**
 * 404 template.
 *
 * @package RealtRigel
 */

get_header();
?>
<div class="container">
	<section class="error-404 not-found">
		<h1><?php esc_html_e( 'Page not found', 'realtrigel' ); ?></h1>
		<p><?php esc_html_e( 'The page you are looking for does not exist.', 'realtrigel' ); ?></p>
		<?php get_search_form(); ?>
	</section>
</div>
<?php
get_footer();

