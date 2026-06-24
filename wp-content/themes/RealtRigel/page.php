<?php
/**
 * Page template.
 *
 * @package RealtRigel
 */

get_header();
?>
<div class="container">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'page' );
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</div>
<?php
get_footer();

