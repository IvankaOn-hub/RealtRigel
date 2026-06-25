<?php
/**
 * Testimonials section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="rtg-section rtg-testimonials">
	<div class="container">
		<div class="rtg-testimonials__box">
			<div class="rtg-section__header">
				<h2>Co mówią nasi klienci</h2>
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
				<div class="rtg-slider swiper" data-slider="testimonials">
				<div class="swiper-wrapper">

					<div class="swiper-slide">
						<article class="rtg-testimonial-card">
							<i class="fa-solid fa-quote-left rtg-testimonial-card__quote"></i>

							<p>Profesjonalizm na najwyższym poziomie. Cały proces przebiegł sprawnie i bez żadnych problemów.</p>

							<div class="rtg-testimonial-card__author">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<div>
									<strong>Joanna Kowalska</strong>
									<span>Zakup apartamentu</span>
								</div>
							</div>

							<i class="fa-solid fa-quote-right rtg-testimonial-card__quote rtg-testimonial-card__quote--end"></i>
						</article>
					</div>

					<div class="swiper-slide">
						<article class="rtg-testimonial-card">
							<i class="fa-solid fa-quote-left rtg-testimonial-card__quote"></i>

							<p>Dzięki RealRigel znaleźliśmy idealny lokal pod naszą działalność. Polecam z całego serca!</p>

							<div class="rtg-testimonial-card__author">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<div>
									<strong>Marek Nowak</strong>
									<span>Wynajem lokalu</span>
								</div>
							</div>

							<i class="fa-solid fa-quote-right rtg-testimonial-card__quote rtg-testimonial-card__quote--end"></i>
						</article>
					</div>

					<div class="swiper-slide">
						<article class="rtg-testimonial-card">
							<i class="fa-solid fa-quote-left rtg-testimonial-card__quote"></i>

							<p>Dzięki RealRigel znaleźliśmy idealny lokal pod naszą działalność. Polecam z całego serca! Polecam z całego serca! Polecam z całego serca!</p>

							<div class="rtg-testimonial-card__author">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<div>
									<strong>Marek Nowak</strong>
									<span>Wynajem lokalu</span>
								</div>
							</div>

							<i class="fa-solid fa-quote-right rtg-testimonial-card__quote rtg-testimonial-card__quote--end"></i>
						</article>
					</div>

					<div class="swiper-slide">
						<article class="rtg-testimonial-card">
							<i class="fa-solid fa-quote-left rtg-testimonial-card__quote"></i>

							<p>Dzięki RealRigel znaleźliśmy idealny lokal pod naszą działalność. Polecam z całego serca!</p>

							<div class="rtg-testimonial-card__author">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<div>
									<strong>Marek Nowak</strong>
									<span>Wynajem lokalu</span>
								</div>
							</div>

							<i class="fa-solid fa-quote-right rtg-testimonial-card__quote rtg-testimonial-card__quote--end"></i>
						</article>
					</div>

					<div class="swiper-slide">
						<article class="rtg-testimonial-card">
							<i class="fa-solid fa-quote-left rtg-testimonial-card__quote"></i>

							<p>Zespół wykazał się ogromnym zaangażowaniem i wiedzą. Na pewno wrócimy!</p>

							<div class="rtg-testimonial-card__author">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/avatar.jpg' ); ?>" alt="">
								<div>
									<strong>Katarzyna Wiśniewska</strong>
									<span>Sprzedaż mieszkania</span>
								</div>
							</div>

							<i class="fa-solid fa-quote-right rtg-testimonial-card__quote rtg-testimonial-card__quote--end"></i>
						</article>
					</div>

				</div>
                
			</div>

			<div class="rtg-slider__bottom">
					<div class="swiper-pagination"></div>
				</div>
			</div>

			
		</div>
	</div>
</section>