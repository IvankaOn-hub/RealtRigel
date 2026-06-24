<?php
/**
 * Service post type registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Service_Post_Type {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'service';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register service post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'               => __( 'Услуги', 'realtrigel-core' ),
			'singular_name'      => __( 'Услуга', 'realtrigel-core' ),
			'add_new'            => __( 'Добавить услугу', 'realtrigel-core' ),
			'add_new_item'       => __( 'Добавить новую услугу', 'realtrigel-core' ),
			'edit_item'          => __( 'Редактировать услугу', 'realtrigel-core' ),
			'new_item'           => __( 'Новая услуга', 'realtrigel-core' ),
			'view_item'          => __( 'Просмотр услуги', 'realtrigel-core' ),
			'search_items'       => __( 'Поиск услуг', 'realtrigel-core' ),
			'not_found'          => __( 'Услуги не найдены', 'realtrigel-core' ),
			'not_found_in_trash' => __( 'В корзине услуг нет', 'realtrigel-core' ),
			'all_items'          => __( 'Все услуги', 'realtrigel-core' ),
			'menu_name'          => __( 'Услуги', 'realtrigel-core' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => array(
				'slug'       => 'services',
				'with_front' => false,
			),
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-megaphone',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
