<?php
/**
 * Front page template.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

	get_template_part( 'template-parts/home/hero' );
	get_template_part( 'template-parts/home/featured' );
	get_template_part( 'template-parts/home/catalog' );
	get_template_part( 'template-parts/home/categories' );
	get_template_part( 'template-parts/home/stats' );
	get_template_part( 'template-parts/home/about' );
	get_template_part( 'template-parts/home/benefits' );
	get_template_part( 'template-parts/home/cta' );
	get_template_part( 'template-parts/home/testimonials' );
	get_template_part( 'template-parts/home/blog' );
	get_template_part( 'template-parts/home/faq' );

get_footer();