<?php
/**
 * Featured properties section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="rtg-section">
	<div class="container">

		<div class="grid grid--2">

			<div class="rtg-about__image">
				<img
					src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/about-us.jpg' ); ?>"
					alt="Founder RealtRigel"
				>
			</div>

			<div class="rtg-about__content">


				<h2>
					О компании
				</h2>

				<p>
                    It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged.
				</p>

				<p>
					It was popularised thanks to these sheets and more recently with desktop publishing software like Aldus PageMaker and Microsoft Word including versions of Lorem Ipsum.
				</p>

				<div class="rtg-team-slider swiper" data-slider="team">
					<div class="swiper-wrapper">

						<div class="swiper-slide">
							<div class="rtg-team-card">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<strong>Full name</strong>
								<span>CEO & Founder</span>
							</div>
						</div>

						<div class="swiper-slide">
							<div class="rtg-team-card">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<strong>Full name</strong>
								<span>Sales Director</span>
							</div>
						</div>

						<div class="swiper-slide">
							<div class="rtg-team-card">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<strong>Full name</strong>
								<span>Property Advisor</span>
							</div>
						</div>
						<div class="swiper-slide">
							<div class="rtg-team-card">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<strong>Full name</strong>
								<span>Property Advisor</span>
							</div>
						</div>

					</div>

					 <div class="rtg-slider__bottom">
						<div class="swiper-pagination"></div>
					</div>
				</div>

				<a href="#" class="btn btn--primary">
					Подробнее о компании
				</a>

			</div>

		</div>

	</div>
</section>