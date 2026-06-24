<?php
/**
 * Article post type registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Article_Post_Type {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'article';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register article post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'               => __( 'SEO-статьи', 'realtrigel-core' ),
			'singular_name'      => __( 'SEO-статья', 'realtrigel-core' ),
			'add_new'            => __( 'Добавить статью', 'realtrigel-core' ),
			'add_new_item'       => __( 'Добавить новую SEO-статью', 'realtrigel-core' ),
			'edit_item'          => __( 'Редактировать SEO-статью', 'realtrigel-core' ),
			'new_item'           => __( 'Новая SEO-статья', 'realtrigel-core' ),
			'view_item'          => __( 'Просмотр SEO-статьи', 'realtrigel-core' ),
			'search_items'       => __( 'Поиск SEO-статей', 'realtrigel-core' ),
			'not_found'          => __( 'SEO-статьи не найдены', 'realtrigel-core' ),
			'not_found_in_trash' => __( 'В корзине SEO-статей нет', 'realtrigel-core' ),
			'all_items'          => __( 'Все SEO-статьи', 'realtrigel-core' ),
			'menu_name'          => __( 'SEO-статьи', 'realtrigel-core' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => false,
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-media-document',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
