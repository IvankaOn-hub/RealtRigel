<?php
/**
 * Catalog helper functions.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'rr_get_catalog_page_template_slug' ) ) {
	/**
	 * Get catalog page template slug.
	 *
	 * @return string
	 */
	function rr_get_catalog_page_template_slug(): string {
		return 'template-catalog-page.php';
	}
}

if ( ! function_exists( 'rr_page_has_catalog_blocks' ) ) {
	/**
	 * Determine whether a page contains catalog blocks.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	function rr_page_has_catalog_blocks( int $page_id ): bool {
		if ( $page_id <= 0 ) {
			return false;
		}

		$page = get_post( $page_id );

		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return false;
		}

		$content = (string) $page->post_content;

		if ( '' === $content ) {
			return false;
		}

		return has_block( 'realtrigel/catalog-search', $content ) || has_block( 'realtrigel/catalog-properties', $content );
	}
}

if ( ! function_exists( 'rr_get_catalog_page_id' ) ) {
	/**
	 * Get the published catalog page ID by assigned template.
	 *
	 * @return int
	 */
	function rr_get_catalog_page_id(): int {
		static $page_id = null;

		if ( null !== $page_id ) {
			return $page_id;
		}

		$page_ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template',
				'meta_value'     => rr_get_catalog_page_template_slug(),
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);

		$page_id = empty( $page_ids ) ? 0 : (int) $page_ids[0];

		if ( $page_id <= 0 ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( rr_page_has_catalog_blocks( $front_page_id ) ) {
				$page_id = $front_page_id;
			}
		}

		return $page_id;
	}
}

if ( ! function_exists( 'rr_is_catalog_page' ) ) {
	/**
	 * Determine whether the current request is the catalog page.
	 *
	 * @return bool
	 */
	function rr_is_catalog_page(): bool {
		if ( ! is_page() ) {
			return false;
		}

		$page_id = rr_get_catalog_page_id();

		if ( $page_id > 0 ) {
			return is_page( $page_id );
		}

		$queried_object_id = get_queried_object_id();

		if ( $queried_object_id <= 0 ) {
			return false;
		}

		return rr_get_catalog_page_template_slug() === get_page_template_slug( $queried_object_id );
	}
}

if ( ! function_exists( 'rr_get_catalog_page_url' ) ) {
	/**
	 * Get the catalog page URL.
	 *
	 * @return string
	 */
function rr_get_catalog_page_url(): string {
		$page_id = rr_get_catalog_page_id();

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );

			return is_string( $url ) ? $url : '';
		}

	return '';
	}
}

if ( ! function_exists( 'rr_get_catalog_map_payload' ) ) {
	/**
	 * Get safe map payload for a catalog object.
	 *
	 * @param int $post_id Catalog post ID.
	 * @return array<string, mixed>|null
	 */
	function rr_get_catalog_map_payload( int $post_id ): ?array {
		if ( ! class_exists( 'RR_Catalog_Map_Manager' ) ) {
			return null;
		}

		return RR_Catalog_Map_Manager::build_map_payload( $post_id );
	}
}
