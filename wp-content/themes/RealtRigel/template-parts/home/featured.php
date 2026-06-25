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

<section class="rtg-section rtg-featured">
	<div class="container">

		<div class="rtg-section__header">
			<h2>Популярные</h2>
			<a href="#" class="btn-link--icon">
				Смотреть все
				<i class="fa-solid fa-arrow-right text-accent"></i>
			</a>
		</div>
		<div class="rtg-slider-wrap">
			<div class="rtg-slider__nav">
					<button data-slider-prev type="button" aria-label="Poprzedni slajd">
						<i class="fa-solid fa-angle-left"></i>
					</button>

					<button data-slider-next type="button" aria-label="Następny slajd">
						<i class="fa-solid fa-angle-right"></i>
					</button>
				</div>
			<div class="rtg-slider swiper" data-slider="featured">
				<div class="swiper-wrapper">
					
					<div class="swiper-slide">
						<article class="rtg-dark-card">
							<div class="rtg-dark-card__media">
								<img
									src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>"
									alt=""
									class="rtg-img-cover rtg-img-absolute"
								>

								<div class="rtg-dark-card__overlay"></div>

								<span class="rtg-badge">
									Lorem ipsum
								</span>


								<div class="rtg-dark-card__content">
									<h3>Lorem ipsum dolor sit amet </h3>

									<p class="rtg-dark-card__location">
										<i class="fa-solid fa-location-dot"></i>
										Lorem ipsum
									</p>

									<ul class="rtg-card__meta">
										<li>
											<i class="fa-solid fa-ruler-combined"></i>
											<span>320 m²</span>
										</li>
										<li>
											<i class="fa-solid fa-bed"></i>
											<span>6 pokoi</span>
										</li>
										<li>
											<i class="fa-solid fa-bath"></i>
											<span>3 łazienki</span>
										</li>
										<li>
											<i class="fa-solid fa-table-cells"></i>
											<span>Działka 1200 m²</span>
										</li>
									</ul>

									<div class="rtg-dark-card__footer">
										<p class="rtg-dark-card__price">
											4 900 000 <span>PLN</span>
										</p>

										<a href="#" class="btn btn--outline">
											Lorem ipsum
											<i class="fa-solid fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>
						</article>
					</div>
					<div class="swiper-slide">
						<article class="rtg-dark-card">
							<div class="rtg-dark-card__media">
								<img
									src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>"
									alt=""
									class="rtg-img-cover rtg-img-absolute"
								>

								<div class="rtg-dark-card__overlay"></div>

								<span class="rtg-badge">
									Lorem ipsum
								</span>


								<div class="rtg-dark-card__content">
									<h3>Lorem ipsum dolor</h3>

									<p class="rtg-dark-card__location">
										<i class="fa-solid fa-location-dot"></i>
										Lorem ipsum
									</p>

									<ul class="rtg-card__meta">
										<li>
											<i class="fa-solid fa-ruler-combined"></i>
											<span>320 m²</span>
										</li>
										<li>
											<i class="fa-solid fa-bed"></i>
											<span>6 pokoi</span>
										</li>
										<li>
											<i class="fa-solid fa-bath"></i>
											<span>3 łazienki</span>
										</li>
										<li>
											<i class="fa-solid fa-table-cells"></i>
											<span>Działka 1200 m²</span>
										</li>
									</ul>

									<div class="rtg-dark-card__footer">
										<p class="rtg-dark-card__price">
											4 900 000 <span>PLN</span>
										</p>

										<a href="#" class="btn btn--outline">
											Lorem ipsum
											<i class="fa-solid fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>
						</article>
					</div>
					<div class="swiper-slide">
						<article class="rtg-dark-card">
							<div class="rtg-dark-card__media">
								<img
									src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>"
									alt=""
									class="rtg-img-cover rtg-img-absolute"
								>

								<div class="rtg-dark-card__overlay"></div>

								<span class="rtg-badge">
									Lorem ipsum
								</span>


								<div class="rtg-dark-card__content">
									<h3>Lorem ipsum dolor sit amet, Lorem ipsum dolor</h3>

									<p class="rtg-dark-card__location">
										<i class="fa-solid fa-location-dot"></i>
										Lorem ipsum
									</p>

									<ul class="rtg-card__meta">
										<li>
											<i class="fa-solid fa-ruler-combined"></i>
											<span>320 m²</span>
										</li>
										<li>
											<i class="fa-solid fa-bed"></i>
											<span>6 pokoi</span>
										</li>
										<li>
											<i class="fa-solid fa-bath"></i>
											<span>3 łazienki</span>
										</li>
										<li>
											<i class="fa-solid fa-table-cells"></i>
											<span>Działka 1200 m²</span>
										</li>
									</ul>

									<div class="rtg-dark-card__footer">
										<p class="rtg-dark-card__price">
											4 900 000 <span>PLN</span>
										</p>

										<a href="#" class="btn btn--outline">
											Lorem ipsum
											<i class="fa-solid fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>
						</article>
					</div>
					<div class="swiper-slide">
						<article class="rtg-dark-card">
							<div class="rtg-dark-card__media">
								<img
									src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>"
									alt=""
									class="rtg-img-cover rtg-img-absolute"
								>

								<div class="rtg-dark-card__overlay"></div>

								<span class="rtg-badge">
									Lorem ipsum
								</span>


								<div class="rtg-dark-card__content">
									<h3>Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet</h3>

									<p class="rtg-dark-card__location">
										<i class="fa-solid fa-location-dot"></i>
										Lorem ipsum
									</p>

									<ul class="rtg-card__meta">
										<li>
											<i class="fa-solid fa-ruler-combined"></i>
											<span>320 m²</span>
										</li>
										<li>
											<i class="fa-solid fa-bed"></i>
											<span>6 pokoi</span>
										</li>
										<li>
											<i class="fa-solid fa-bath"></i>
											<span>3 łazienki</span>
										</li>
										<li>
											<i class="fa-solid fa-table-cells"></i>
											<span>Działka 1200 m²</span>
										</li>
									</ul>

									<div class="rtg-dark-card__footer">
										<p class="rtg-dark-card__price">
											4 900 000 <span>PLN</span>
										</p>

										<a href="#" class="btn btn--outline">
											Lorem ipsum
											<i class="fa-solid fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>
						</article>
					</div>
					<div class="swiper-slide">
						<article class="rtg-dark-card">
							<div class="rtg-dark-card__media">
								<img
									src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>"
									alt=""
									class="rtg-img-cover rtg-img-absolute"
								>

								<div class="rtg-dark-card__overlay"></div>

								<span class="rtg-badge">
									Lorem ipsum
								</span>


								<div class="rtg-dark-card__content">
									<h3>Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet</h3>

									<p class="rtg-dark-card__location">
										<i class="fa-solid fa-location-dot"></i>
										Lorem ipsum
									</p>

									<ul class="rtg-card__meta">
										<li>
											<i class="fa-solid fa-ruler-combined"></i>
											<span>320 m²</span>
										</li>
										<li>
											<i class="fa-solid fa-bed"></i>
											<span>6 pokoi</span>
										</li>
										<li>
											<i class="fa-solid fa-bath"></i>
											<span>3 łazienki</span>
										</li>
										<li>
											<i class="fa-solid fa-table-cells"></i>
											<span>Działka 1200 m²</span>
										</li>
									</ul>

									<div class="rtg-dark-card__footer">
										<p class="rtg-dark-card__price">
											4 900 000 <span>PLN</span>
										</p>

										<a href="#" class="btn btn--outline">
											Lorem ipsum
											<i class="fa-solid fa-arrow-right"></i>
										</a>
									</div>
								</div>
							</div>
						</article>
					</div>
				</div>
			</div>
				<div class="rtg-slider__bottom">
					<div class="swiper-pagination"></div>
				</div>
		</div>
		
	</div>
</section>