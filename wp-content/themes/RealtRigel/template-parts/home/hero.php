<?php
/**
 * Home hero section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="rtg-hero">
	<div class="rtg-hero__container">
		<img class="rtg-img-cover rtg-img-absolute" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero.jpg' ); ?>" alt="">
		<!-- <video class="rtg-hero__video" autoplay="" muted="" loop="" playsinline="">
			<source src="<?php echo esc_url( get_template_directory_uri() . '/assets/video/hero.mp4' ); ?>">
		</video> -->
		<div class="rtg-hero__overlay"></div>

		<div class="container rtg-hero__inner">
			<div class="rtg-hero__content">
				<h1>
					Lorem Ipsum is simply dummy text <span class="text-accent">of the printing.</span>
				</h1>

				<p >
					Lorem Ipsum has been the industry's standard dummy text ever since 1966, when designers at Letraset and James Mosley
				</p>
			</div>
		</div>
	</div>
	
	<div class="rtg-hero__search">
		<?php echo do_blocks( '<!-- wp:realtrigel/catalog-search /-->' ); ?>
	</div>
</section>