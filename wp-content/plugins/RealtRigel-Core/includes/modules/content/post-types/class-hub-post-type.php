<?php
/**
 * Hub post type registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Hub_Post_Type {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'hub';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register hub post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'               => __( 'Хабы', 'realtrigel-core' ),
			'singular_name'      => __( 'Хаб', 'realtrigel-core' ),
			'add_new'            => __( 'Добавить хаб', 'realtrigel-core' ),
			'add_new_item'       => __( 'Добавить новый хаб', 'realtrigel-core' ),
			'edit_item'          => __( 'Редактировать хаб', 'realtrigel-core' ),
			'new_item'           => __( 'Новый хаб', 'realtrigel-core' ),
			'view_item'          => __( 'Просмотр хаба', 'realtrigel-core' ),
			'search_items'       => __( 'Поиск хабов', 'realtrigel-core' ),
			'not_found'          => __( 'Хабы не найдены', 'realtrigel-core' ),
			'not_found_in_trash' => __( 'В корзине хабов нет', 'realtrigel-core' ),
			'all_items'          => __( 'Все хабы', 'realtrigel-core' ),
			'menu_name'          => __( 'Хабы', 'realtrigel-core' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'hierarchical'  => true,
			'has_archive'   => false,
			'rewrite'       => false,
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-networking',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
