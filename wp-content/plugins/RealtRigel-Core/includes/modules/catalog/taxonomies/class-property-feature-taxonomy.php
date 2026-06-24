<?php
/**
 * Property feature taxonomy registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Feature_Taxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'property_feature';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'current_screen', array( $this, 'add_admin_help' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
	}

	/**
	 * Register taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                       => __( 'Дополнительные характеристики объекта', 'realtrigel-core' ),
			'singular_name'              => __( 'Дополнительная характеристика объекта', 'realtrigel-core' ),
			'search_items'               => __( 'Искать характеристики', 'realtrigel-core' ),
			'all_items'                  => __( 'Все характеристики', 'realtrigel-core' ),
			'edit_item'                  => __( 'Редактировать характеристику', 'realtrigel-core' ),
			'update_item'                => __( 'Обновить характеристику', 'realtrigel-core' ),
			'add_new_item'               => __( 'Добавить характеристику', 'realtrigel-core' ),
			'new_item_name'              => __( 'Название новой характеристики', 'realtrigel-core' ),
			'menu_name'                  => __( 'Дополнительные характеристики объекта', 'realtrigel-core' ),
			'popular_items'              => __( 'Популярные характеристики', 'realtrigel-core' ),
			'separate_items_with_commas' => __( 'Разделяйте характеристики запятыми', 'realtrigel-core' ),
			'add_or_remove_items'        => __( 'Добавить или удалить характеристики', 'realtrigel-core' ),
			'choose_from_most_used'      => __( 'Выбрать из часто используемых характеристик', 'realtrigel-core' ),
			'not_found'                  => __( 'Характеристики не найдены.', 'realtrigel-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Здесь создаются дополнительные характеристики, которые часто повторяются у объектов или важны для фильтрации. Например: продажа со spółką, мониторинг, парковка, с ремонтом.', 'realtrigel-core' ),
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => false,
			'rewrite'           => array(
				'slug'       => 'property-feature',
				'with_front' => false,
			),
		);

		register_taxonomy( self::TAXONOMY, array( RR_Property_Post_Type::POST_TYPE ), $args );
	}

	/**
	 * Add help text on taxonomy admin screen.
	 *
	 * @param WP_Screen $screen Current screen.
	 *
	 * @return void
	 */
	public function add_admin_help( $screen ): void {
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}

		if ( 'edit-' . self::TAXONOMY !== $screen->id ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => self::TAXONOMY . '-help',
				'title'   => __( 'Что это', 'realtrigel-core' ),
				'content' => '<p>' . esc_html__( 'Это список дополнительных характеристик объекта. Сюда удобно выносить свойства, которые часто повторяются или нужны для фильтрации в каталоге. Например: продажа со spółką, мониторинг, парковка, с ремонтом.', 'realtrigel-core' ) . '</p>',
			)
		);
	}

	/**
	 * Render visible admin notice on taxonomy terms screen.
	 *
	 * @return void
	 */
	public function render_admin_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}

		if ( 'edit-' . self::TAXONOMY !== $screen->id ) {
			return;
		}

		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'Здесь создаются дополнительные характеристики объекта, которые часто повторяются или важны для фильтрации. Например: продажа со spółką, мониторинг, парковка, с ремонтом.', 'realtrigel-core' );
		echo '</p></div>';
	}
}
