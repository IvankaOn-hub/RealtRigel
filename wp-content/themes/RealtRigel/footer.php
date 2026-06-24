<?php
/**
 * Theme footer.
 *
 * @package RealtRigel
 */

$footer_menu_location = has_nav_menu( 'footer' ) ? 'footer' : 'primary';
$admin_email          = (string) get_option( 'admin_email' );
$site_description     = get_bloginfo( 'description' );

?>
</main>
<footer class="site-footer rtg-footer">
	<div class="rtg-footer__shell">
		<div class="container">
			<div class="rtg-footer__top">
				<div class="rtg-footer__brand">
					<?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
						<div class="rtg-footer__logo rtg-footer__logo--image">
							<?php the_custom_logo(); ?>
						</div>
					<?php else : ?>
						<a class="rtg-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<?php bloginfo( 'name' ); ?>
						</a>
					<?php endif; ?>

					<p class="rtg-footer__lead">
						<?php echo esc_html( '' !== $site_description ? $site_description : __( 'Коммерческая недвижимость и инвестиционные объекты в Польше.', 'realtrigel' ) ); ?>
					</p>

					<div class="rtg-footer__socials">
						<a href="#" aria-label="Facebook">
							<i class="fa-brands fa-facebook-f"></i>
						</a>

						<a href="#" aria-label="Instagram">
							<i class="fa-brands fa-instagram"></i>
						</a>

						<a href="#" aria-label="LinkedIn">
							<i class="fa-brands fa-linkedin-in"></i>
						</a>

						<a href="#" aria-label="YouTube">
							<i class="fa-brands fa-youtube"></i>
						</a>
					</div>

				</div>

				<div class="rtg-footer__grid">
					<div class="rtg-footer__column">
						<h2 class="rtg-footer__title"><?php esc_html_e( 'Навигация', 'realtrigel' ); ?></h2>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => $footer_menu_location,
								'container'      => false,
								'fallback_cb'    => false,
								'menu_class'     => 'rtg-footer__menu',
							)
						);
						?>
					</div>

					<div class="rtg-footer__column">
						<h2 class="rtg-footer__title"><?php esc_html_e( 'Контакты', 'realtrigel' ); ?></h2>
						<div class="rtg-footer__links">
							<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Связаться с нами', 'realtrigel' ); ?></a>
							<?php if ( '' !== $admin_email ) : ?>
								<a href="mailto:<?php echo esc_attr( antispambot( $admin_email ) ); ?>"><?php echo esc_html( antispambot( $admin_email ) ); ?></a>
							<?php endif; ?>
						</div>
					</div>

					<div class="rtg-footer__column">
						<h2 class="rtg-footer__title"><?php esc_html_e( 'Направления', 'realtrigel' ); ?></h2>
						<div class="rtg-footer__meta">
							<p><?php esc_html_e( 'Каталог объектов', 'realtrigel' ); ?></p>
							<p><?php esc_html_e( 'Продажа и аренда', 'realtrigel' ); ?></p>
							<p><?php esc_html_e( 'Польша', 'realtrigel' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="rtg-footer__bottom">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'Перейти в каталог', 'realtrigel' ); ?></a>
			</div>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
<script>
  (function(d,t) {
    var BASE_URL="https://desk-187-127-74-128.nip.io";
    var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=BASE_URL+"/packs/js/sdk.js";
    g.defer = true;
    g.async = true;
    s.parentNode.insertBefore(g,s);
    g.onload=function(){
      window.chatwootSDK.run({
        websiteToken: 'Kkv5z2JD5weiwyViWTT4uG8u',
        baseUrl: BASE_URL
      })
    }
  })(document,"script");
</script>
</body>
</html>
