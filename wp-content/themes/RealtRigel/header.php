<?php
/**
 * Theme header.
 *
 * @package RealtRigel
 */

$current_locale   = determine_locale();
$current_language = strtoupper( substr( $current_locale, 0, 2 ) );
$language_items   = array();

if ( has_filter( 'wpml_active_languages' ) ) {
	$wpml_items = apply_filters(
		'wpml_active_languages',
		null,
		array(
			'skip_missing' => 0,
			'orderby'      => 'code',
			'order'        => 'asc',
		)
	);

	if ( is_array( $wpml_items ) ) {
		foreach ( $wpml_items as $wpml_item ) {
			if ( empty( $wpml_item['language_code'] ) ) {
				continue;
			}

			$language_items[] = array(
				'label'   => strtoupper( (string) $wpml_item['language_code'] ),
				'url'     => isset( $wpml_item['url'] ) ? (string) $wpml_item['url'] : '#',
				'current' => ! empty( $wpml_item['active'] ),
			);
		}
	}
}

if ( empty( $language_items ) && function_exists( 'pll_the_languages' ) ) {
	$polylang_items = pll_the_languages(
		array(
			'raw'                    => 1,
			'hide_if_no_translation' => 0,
			'hide_current'           => 0,
		)
	);

	if ( is_array( $polylang_items ) ) {
		foreach ( $polylang_items as $polylang_item ) {
			if ( empty( $polylang_item['slug'] ) ) {
				continue;
			}

			$language_items[] = array(
				'label'   => strtoupper( (string) $polylang_item['slug'] ),
				'url'     => isset( $polylang_item['url'] ) ? (string) $polylang_item['url'] : '#',
				'current' => ! empty( $polylang_item['current_lang'] ),
			);
		}
	}
}

if ( empty( $language_items ) ) {
	$language_items[] = array(
		'label'   => $current_language,
		'url'     => home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ),
		'current' => true,
	);
}

$currency_items = array( 'PLN', 'EUR', 'USD' );

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header rtg-header" data-rtg-header>
	<div class="container rtg-header__inner">
		<div class="rtg-header__brand">
			<?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
				<div class="rtg-header__logo rtg-header__logo--image">
					<?php the_custom_logo(); ?>
				</div>
			<?php else : ?>
				<a class="rtg-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'На главную', 'realtrigel' ); ?>">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<nav class="rtg-header__nav primary-nav" aria-label="<?php esc_attr_e( 'Основное меню', 'realtrigel' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'rtg-header__menu',
				)
			);
			?>
		</nav>

		<div class="rtg-header__actions rtg-header__actions--desktop">

			<!-- <a href="tel:+48123456789" class="btn-link">
				<i class="fa-solid fa-phone"></i>
			</a> -->

			<div class="rtg-header__languages" aria-label="<?php esc_attr_e( 'Переключатель языков', 'realtrigel' ); ?>">
				<label class="rtg-header__language-select-wrap">
					<span class="screen-reader-text"><?php esc_html_e( 'Язык', 'realtrigel' ); ?></span>
					<select class="rtg-header__language-select" data-rtg-language-select>
						<?php foreach ( $language_items as $language_item ) : ?>
							<option value="<?php echo esc_url( $language_item['url'] ); ?>" <?php selected( ! empty( $language_item['current'] ) ); ?>>
								<?php echo esc_html( $language_item['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="rtg-header__currency" aria-label="<?php esc_attr_e( 'Выбор валюты', 'realtrigel' ); ?>">
				<label class="rtg-header__currency-select-wrap">
					<span class="screen-reader-text"><?php esc_html_e( 'Валюта', 'realtrigel' ); ?></span>
					<select class="rtg-header__currency-select" data-rtg-currency-select>
						<?php foreach ( $currency_items as $currency_code ) : ?>
							<option value="<?php echo esc_attr( $currency_code ); ?>" <?php selected( 'PLN', $currency_code ); ?>><?php echo esc_html( $currency_code ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
	
			<button type="button" class="btn btn--primary" data-rtg-contact-open><?php esc_html_e( 'Получить консультацию', 'realtrigel' ); ?></button>
			
		</div>

		<div class="rtg-header__mobile-tools">
			<div class="rtg-header__languages" aria-label="<?php esc_attr_e( 'Переключатель языков', 'realtrigel' ); ?>">
				<label class="rtg-header__language-select-wrap">
					<span class="screen-reader-text"><?php esc_html_e( 'Язык', 'realtrigel' ); ?></span>
					<select class="rtg-header__language-select" data-rtg-language-select>
						<?php foreach ( $language_items as $language_item ) : ?>
							<option value="<?php echo esc_url( $language_item['url'] ); ?>" <?php selected( ! empty( $language_item['current'] ) ); ?>>
								<?php echo esc_html( $language_item['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="rtg-header__mobile-currency" aria-label="<?php esc_attr_e( 'Выбор валюты', 'realtrigel' ); ?>">
				<label class="rtg-header__currency-select-wrap">
					<span class="screen-reader-text"><?php esc_html_e( 'Валюта', 'realtrigel' ); ?></span>
					<select class="rtg-header__currency-select" data-rtg-currency-select>
						<?php foreach ( $currency_items as $currency_code ) : ?>
							<option value="<?php echo esc_attr( $currency_code ); ?>" <?php selected( 'PLN', $currency_code ); ?>><?php echo esc_html( $currency_code ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<button type="button" class="rtg-header__burger" data-rtg-header-toggle aria-expanded="false" aria-controls="rtg-mobile-menu">
				<span></span>
				<span></span>
				<span></span>
			</button>
		</div>
	</div>

	<div class="rtg-header__mobile-panel" id="rtg-mobile-menu" data-rtg-header-panel hidden>
		<div class="container rtg-header__mobile-panel-inner">
			<nav class="rtg-header__mobile-nav" aria-label="<?php esc_attr_e( 'Мобильное меню', 'realtrigel' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'fallback_cb'    => false,
						'menu_class'     => 'rtg-header__mobile-menu',
					)
				);
				?>
			</nav>
		</div>
	</div>
</header>
<main class="site-main">
