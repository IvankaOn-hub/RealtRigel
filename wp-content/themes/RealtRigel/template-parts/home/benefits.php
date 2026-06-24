<?php
/**
 * Benefits section.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="rtg-section rtg-benefits">
	<div class="container">
		<div class="rtg-section__header">
			<h2>Почему стоит сотрудничать с RealRigel?</h2>
		</div>

		<div class="grid grid--2 grid-reverse">
			<div class="rtg-benefits__media order-2">
				<img
					src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/about-us.jpg' ); ?>"
					alt=""
					class="rtg-img-cover"
				>
			</div>

			<div class="grid grid--2 order-1">
				<div class="rtg-light-card">
					<i class="icon-round-md fa-solid fa-handshake"></i>
					<h3>What is Lorem Ipsum</h3>
					<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy</p>
				</div>

				<div class="rtg-light-card">
					<i class="icon-round-md fa-solid fa-heart"></i>
					<h3>What is Lorem Ipsum</h3>
					<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
				</div>

				<div class="rtg-light-card">
					<i class="icon-round-md fa-solid fa-location-dot"></i>
					<h3>What is Lorem Ipsum</h3>
					<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy</p>
				</div>

				<div class="rtg-light-card">
					<i class="icon-round-md fa-solid fa-clipboard-check"></i>
					<h3>What is Lorem Ipsum</h3>
					<p>Lorem Ipsum has been the industry's standard dummy</p>
				</div>
			</div>
		</div>
	</div>
</section>