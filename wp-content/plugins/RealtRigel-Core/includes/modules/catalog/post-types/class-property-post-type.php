<?php
/**
 * Property post type registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Post_Type {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'catalog';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register the property post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'               => __( 'Объекты', 'realtrigel-core' ),
			'singular_name'      => __( 'Объект', 'realtrigel-core' ),
			'add_new'            => __( 'Добавить объект', 'realtrigel-core' ),
			'add_new_item'       => __( 'Добавить новый объект', 'realtrigel-core' ),
			'edit_item'          => __( 'Редактировать объект', 'realtrigel-core' ),
			'new_item'           => __( 'Новый объект', 'realtrigel-core' ),
			'view_item'          => __( 'Просмотр объекта', 'realtrigel-core' ),
			'search_items'       => __( 'Поиск объектов', 'realtrigel-core' ),
			'not_found'          => __( 'Объекты не найдены', 'realtrigel-core' ),
			'not_found_in_trash' => __( 'В корзине объектов нет', 'realtrigel-core' ),
			'all_items'          => __( 'Все объекты', 'realtrigel-core' ),
			'menu_name'          => __( 'Каталог', 'realtrigel-core' ),
		);

		$args = array(
			'labels'       => $labels,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'has_archive'  => false,
			'rewrite'      => array(
				'slug'       => 'catalog',
				'with_front' => false,
			),
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
