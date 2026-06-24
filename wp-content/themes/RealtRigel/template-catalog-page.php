<?php
/**
 * Template Name: Catalog Page
 * Template Post Type: page
 *
 * Catalog page template.
 *
 * @package RealtRigel
 */

get_header();
?>
<div class="container">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'rtg-catalog-page' ); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			</header>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php
get_footer();
