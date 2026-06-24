<?php
/**
 * Content URL and rewrite manager.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Content_Rewrite_Manager {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'request', array( $this, 'resolve_content_request' ) );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 2 );
	}

	/**
	 * Resolve custom hub/article requests from normal pretty URLs.
	 *
	 * @param array<string,mixed> $query_vars Query vars.
	 * @return array<string,mixed>
	 */
	public function resolve_content_request( array $query_vars ): array {
		$path = isset( $query_vars['pagename'] ) ? trim( (string) $query_vars['pagename'], '/' ) : '';

		if ( '' === $path ) {
			return $query_vars;
		}

		$hub = get_page_by_path( $path, OBJECT, RR_Hub_Post_Type::POST_TYPE );
		if ( $hub instanceof WP_Post ) {
			return array(
				'post_type' => RR_Hub_Post_Type::POST_TYPE,
				'p'         => $hub->ID,
			);
		}

		$parts = explode( '/', $path );
		$slug  = sanitize_title( (string) array_pop( $parts ) );

		if ( '' === $slug || empty( $parts ) ) {
			return $query_vars;
		}

		$parent_path = implode( '/', $parts );
		$article     = get_page_by_path( $slug, OBJECT, RR_Article_Post_Type::POST_TYPE );

		if ( $article instanceof WP_Post && $this->article_path_matches( $article, $parent_path ) ) {
			return array(
				'post_type' => RR_Article_Post_Type::POST_TYPE,
				'p'         => $article->ID,
			);
		}

		return $query_vars;
	}

	/**
	 * Filter generated permalinks.
	 *
	 * @param string  $post_link Post link.
	 * @param WP_Post $post      Post object.
	 * @return string
	 */
	public function filter_post_type_link( string $post_link, WP_Post $post ): string {
		if ( RR_Hub_Post_Type::POST_TYPE === $post->post_type ) {
			return home_url( '/' . $this->get_hub_path( $post->ID ) . '/' );
		}

		if ( RR_Article_Post_Type::POST_TYPE === $post->post_type ) {
			$path = $this->get_article_path( $post->ID );
			if ( '' !== $path ) {
				return home_url( '/' . $path . '/' );
			}
		}

		return $post_link;
	}

	/**
	 * Check whether request path matches article hub/subhub path.
	 *
	 * @param WP_Post $article Article post.
	 * @param string  $path    Requested parent path.
	 * @return bool
	 */
	private function article_path_matches( WP_Post $article, string $path ): bool {
		$expected_path = $this->get_article_parent_path( $article->ID );

		return '' !== $expected_path && trim( $path, '/' ) === $expected_path;
	}

	/**
	 * Get article URL path.
	 *
	 * @param int $article_id Article ID.
	 * @return string
	 */
	private function get_article_path( int $article_id ): string {
		$post = get_post( $article_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return '';
		}

		$parent_path = $this->get_article_parent_path( $article_id );
		if ( '' === $parent_path ) {
			return '';
		}

		return trim( $parent_path . '/' . $post->post_name, '/' );
	}

	/**
	 * Get article hub/subhub parent path.
	 *
	 * @param int $article_id Article ID.
	 * @return string
	 */
	private function get_article_parent_path( int $article_id ): string {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$subhub_id = (int) get_field( 'primary_subhub', $article_id );
		$hub_id    = $subhub_id > 0 ? $subhub_id : (int) get_field( 'primary_hub', $article_id );

		if ( $hub_id <= 0 ) {
			return '';
		}

		return $this->get_hub_path( $hub_id );
	}

	/**
	 * Build hub hierarchy path.
	 *
	 * @param int $hub_id Hub ID.
	 * @return string
	 */
	private function get_hub_path( int $hub_id ): string {
		$hub = get_post( $hub_id );
		if ( ! ( $hub instanceof WP_Post ) || RR_Hub_Post_Type::POST_TYPE !== $hub->post_type ) {
			return '';
		}

		$slugs = array( $hub->post_name );
		$parent_id = (int) $hub->post_parent;

		while ( $parent_id > 0 ) {
			$parent = get_post( $parent_id );
			if ( ! ( $parent instanceof WP_Post ) || RR_Hub_Post_Type::POST_TYPE !== $parent->post_type ) {
				break;
			}

			array_unshift( $slugs, $parent->post_name );
			$parent_id = (int) $parent->post_parent;
		}

		return implode( '/', array_filter( $slugs ) );
	}
}
