<?php
/**
 * Categories section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="rtg-section rtg-categories">
	<div class="container">
		<div class="rtg-section__header">
			<h2>Каталог услуг</h2>
		</div>

		<div class="grid grid--3">
			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
				<div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-building"></i>
                    <h4>Покупка</h4>
                </div>
			</a>

			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
                <div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-key"></i>
                    <h4>Аренда</h4>
                </div>
			</a>

			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
                <div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-tag"></i>
                    <h4>Продажа обьекта</h4>
                </div>
			</a>

			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
                <div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-file-signature"></i>
                    <h4>Сдача помещения</h4>
                </div>
			</a>

			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
                <div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-chart-line"></i>
                    <h4>Инвестиции</h4>
                </div>
			</a>

			<a href="#" class="rtg-category-card">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/img-02.jpg' ); ?>" alt="">
                <div class="rtg-category-card__content">
                    <i class="icon-round-md fa-solid fa-scale-balanced"></i>
                    <h4>Юридическая</h4>
                </div>
			</a>
		</div>
	</div>
</section>