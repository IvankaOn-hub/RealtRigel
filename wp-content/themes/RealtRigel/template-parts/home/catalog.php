<?php
/**
 * Catalog section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<section class="rtg-section rtg-home-catalog">
	<div class="container">

		<div class="rtg-section__header">
			<h2>Каталог обьектов</h2>

			<a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>" class="btn-link--icon">
				Перейти в каталог
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
            <div class="rtg-slider swiper" data-slider="catalog">
                <div class="swiper-wrapper">

                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">
                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
                            </div>
                        </article>
                    </div>

                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
                            </div>
                        </article>
                    </div>

                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">
                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
                            </div>
                        </article>
                    </div>
                    
                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
                            </div>
                        </article>
                    </div>

                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">
                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
                            </div>
                        </article>
                    </div>

                    <div class="swiper-slide">
                        <article class="rr-properties-card">
                            <a class="rr-properties-card__media has-gallery" href="#">
                                <div class="rr-properties-card__gallery">
                                    <div class="rr-properties-card__gallery-main">
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="Prime location in central Warsaw">
                                    </div>

                                    <div class="rr-properties-card__gallery-thumbs">
                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>

                                        <span class="rr-properties-card__gallery-thumb">
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                        </span>
                                    </div>
                                </div>
                            </a>

                            <div class="rr-properties-card__content">
                                <h3 class="rr-properties-card__title">
                                    <a href="#">Prime location in central Warsaw near Atrium Plaza</a>
                                </h3>

                                <p class="rr-properties-card__location">
                                    <i class="fa-solid fa-location-dot rr-properties-card__icon rr-properties-card__icon--location"></i>
                                    <span>Warszawa, Centrum</span>
                                </p>

                                <div class="rr-properties-card__footer">
                                    <div class="rr-properties-card__meta">
                                        <div class="rr-properties-card__tags">
                                            <span class="rr-properties-card__tag">Commercial</span>
                                            <span class="rr-properties-card__tag">For rent</span>
                                        </div>

                                        <div class="rr-properties-card__details">
                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat">
                                                        <i class="fa-solid fa-ruler-combined rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>320 m²</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <p class="rr-properties-card__price">4 900 000 PLN</p>
                                                </div>
                                            </div>

                                            <div class="rr-properties-card__detail-row">
                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
                                                    <span class="rr-properties-card__stat rr-properties-card__stat--floor">
                                                        <i class="fa-solid fa-building rr-properties-card__icon rr-properties-card__icon--stat"></i>
                                                        <span>5 piętro</span>
                                                    </span>
                                                </div>

                                                <div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
                                                    <span class="rr-properties-card__stat">15 312 PLN / m²</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a class="rr-properties-card__link" href="#">
                                    View property
                                </a>
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