<section class="rtg-section rtg-blog">
	<div class="container">
		<div class="rtg-section__header">
			<h2>Новые статьи</h2>

			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ) ); ?>" class="btn-link--icon">
				Смотреть все
				<i class="fa-solid fa-arrow-right text-accent"></i>
			</a>
		</div>

		<div class="rtg-blog__row">
            <div class="rtg-slider-wrap">
                <div class="rtg-slider swiper" data-slider="blog">
                    <div class="swiper-wrapper">

                        <div class="swiper-slide">
                            <article class="rtg-blog-card">
                                <a href="#" class="rtg-blog-card__media">
                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                </a>

                                <div class="rtg-blog-card__content">
                                    <div class="rtg-blog-card__meta">
                                        <span class="rr-properties-card__tag">Рынок недвижимости</span>
                                        <time datetime="2026-05-12">12 мая 2026</time>
                                    </div>

                                    <h3>
                                        <a href="#">Lorem Ipsum is simply dummy text of the printing and typesetting industry, text of the printing and typesetting industry..</a>
                                    </h3>
                                </div>
                            </article>
                        </div>

                        <div class="swiper-slide">
                            <article class="rtg-blog-card">
                                <a href="#" class="rtg-blog-card__media">
                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                </a>

                                <div class="rtg-blog-card__content">
                                    <div class="rtg-blog-card__meta">
                                        <span class="rr-properties-card__tag">Продажа</span>
                                        <time datetime="2026-05-08">8 мая 2026</time>
                                    </div>

                                    <h3>
                                        <a href="#">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</a>
                                    </h3>
                                </div>
                            </article>
                        </div>

                        <div class="swiper-slide">
                            <article class="rtg-blog-card">
                                <a href="#" class="rtg-blog-card__media">
                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                </a>

                                <div class="rtg-blog-card__content">
                                    <div class="rtg-blog-card__meta">
                                        <span class="rr-properties-card__tag">Инвестиции</span>
                                        <time datetime="2026-05-05">5 мая 2026</time>
                                    </div>

                                    <h3>
                                        <a href="#">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</a>
                                    </h3>
                                </div>
                            </article>
                        </div>
                        <div class="swiper-slide">
                            <article class="rtg-blog-card">
                                <a href="#" class="rtg-blog-card__media">
                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                </a>

                                <div class="rtg-blog-card__content">
                                    <div class="rtg-blog-card__meta">
                                        <span class="rr-properties-card__tag">Инвестиции</span>
                                        <time datetime="2026-05-05">5 мая 2026</time>
                                    </div>

                                    <h3>
                                        <a href="#">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</a>
                                    </h3>
                                </div>
                            </article>
                        </div>
                        <div class="swiper-slide">
                            <article class="rtg-blog-card">
                                <a href="#" class="rtg-blog-card__media">
                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-01.jpg' ); ?>" alt="">
                                </a>

                                <div class="rtg-blog-card__content">
                                    <div class="rtg-blog-card__meta">
                                        <span class="rr-properties-card__tag">Инвестиции</span>
                                        <time datetime="2026-05-05">5 мая 2026</time>
                                    </div>

                                    <h3>
                                        <a href="#">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</a>
                                    </h3>
                                </div>
                            </article>
                        </div>

                    </div>
                </div>
                <div class="rtg-slider__nav">
                    <button data-slider-prev type="button" aria-label="Poprzedni slajd">
                        <i class="fa-solid fa-angle-left"></i>
                    </button>

                    <button data-slider-next type="button" aria-label="Następny slajd">
                        <i class="fa-solid fa-angle-right"></i>
                    </button>
                </div>

                <div class="rtg-slider__bottom">
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            
            

            <div class="rtg-blog-cta">
                <i class="fa-solid fa-message icon-round-md"></i>
                <h3>Запишитесь на консультацию</h3>
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>

                <a href="#" class="btn btn--primary btn-link--icon">
                    Получить консультацию
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
	</div>
</section>