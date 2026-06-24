<?php
/**
 * Enqueue styles and scripts.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function realtrigel_enqueue_assets(): void {
	$version = wp_get_theme()->get( 'Version' );
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	wp_enqueue_style(
		'realtrigel-fontawesome',
		$theme_uri . '/assets/icons/fontawesome/css/all.min.css',
		array(),
		'7.0.1'
	);

	wp_enqueue_style(
		'realtrigel-swiper',
	$theme_uri . '/assets/css/swiper-bundle.min.css',
	array(),
	realtrigel_asset_version( $theme_dir . '/assets/css/swiper-bundle.min.css', $version )
	);

	$catalog_card_style = WP_PLUGIN_DIR . '/RealtRigel-Core/includes/modules/catalog/blocks/catalog-properties/style.css';

	if ( file_exists( $catalog_card_style ) ) {
		wp_enqueue_style(
			'realtrigel-catalog-properties-card',
			plugins_url( 'includes/modules/catalog/blocks/catalog-properties/style.css', WP_PLUGIN_DIR . '/RealtRigel-Core/RealtRigel-Core.php' ),
			array(),
			filemtime( $catalog_card_style )
		);
	}

	wp_enqueue_style(
		'realtrigel-main',
		$theme_uri . '/assets/css/main.css',
		array(),
		realtrigel_asset_version( $theme_dir . '/assets/css/main.css', $version )
	);

	wp_enqueue_script(
		'realtrigel-swiper',
		$theme_uri . '/assets/js/swiper-bundle.min.js',
		array(),
		realtrigel_asset_version( $theme_dir . '/assets/js/swiper-bundle.min.js', $version ),
		true
	);

	wp_enqueue_script(
		'realtrigel-main',
		$theme_uri . '/assets/js/main.js',
		array(),
		realtrigel_asset_version( $theme_dir . '/assets/js/main.js', $version ),
		true
	);

	if ( is_singular( 'catalog' ) ) {
		wp_enqueue_style(
			'realtrigel-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_enqueue_style(
			'realtrigel-catalog-single',
			$theme_uri . '/assets/css/catalog-single.css',
			array( 'realtrigel-main', 'realtrigel-leaflet' ),
			realtrigel_asset_version( $theme_dir . '/assets/css/catalog-single.css', $version )
		);

		wp_enqueue_script(
			'realtrigel-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_enqueue_script(
			'realtrigel-catalog-single',
			$theme_uri . '/assets/js/catalog-single.js',
			array( 'realtrigel-leaflet' ),
			realtrigel_asset_version( $theme_dir . '/assets/js/catalog-single.js', $version ),
			true
		);

		wp_localize_script(
			'realtrigel-catalog-single',
			'RRTCatalogShare',
			realtrigel_get_catalog_share_payload( get_queried_object_id() )
		);
	}

	if ( realtrigel_should_enqueue_catalog_page_assets() ) {
		wp_enqueue_style(
			'realtrigel-catalog-page',
			$theme_uri . '/assets/css/catalog-page.css',
			array( 'realtrigel-main' ),
			realtrigel_asset_version( $theme_dir . '/assets/css/catalog-page.css', $version )
		);

		wp_enqueue_script(
			'realtrigel-catalog-page',
			$theme_uri . '/assets/js/catalog-page.js',
			array(),
			realtrigel_asset_version( $theme_dir . '/assets/js/catalog-page.js', $version ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'realtrigel_enqueue_assets' );

/**
 * Resolve asset version from file modification time.
 *
 * @param string $asset_path      Absolute asset path.
 * @param string $default_version Fallback version.
 *
 * @return string
 */
function realtrigel_asset_version( string $asset_path, string $default_version ): string {
	if ( file_exists( $asset_path ) ) {
		return (string) filemtime( $asset_path );
	}

	return $default_version;
}

/**
 * Determine whether catalog page assets should be loaded.
 *
 * @return bool
 */
function realtrigel_should_enqueue_catalog_page_assets(): bool {
	if ( function_exists( 'rr_is_catalog_page' ) && rr_is_catalog_page() ) {
		return true;
	}

	if ( is_admin() || ! is_singular() ) {
		return false;
	}

	if ( function_exists( 'rr_has_catalog_listing_block' ) ) {
		return rr_has_catalog_listing_block( get_queried_object_id() );
	}

	return false;
}
