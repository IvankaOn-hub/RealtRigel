<?php
/**
 * Property deal type taxonomy registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Deal_Type_Taxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'property_deal_type';

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
			'name'                       => __( 'Справочник типов сделки', 'realtrigel-core' ),
			'singular_name'              => __( 'Тип сделки', 'realtrigel-core' ),
			'search_items'               => __( 'Искать типы сделки', 'realtrigel-core' ),
			'all_items'                  => __( 'Все типы сделки', 'realtrigel-core' ),
			'parent_item'                => __( 'Родительский тип сделки', 'realtrigel-core' ),
			'parent_item_colon'          => __( 'Родительский тип сделки:', 'realtrigel-core' ),
			'edit_item'                  => __( 'Редактировать тип сделки', 'realtrigel-core' ),
			'update_item'                => __( 'Обновить тип сделки', 'realtrigel-core' ),
			'add_new_item'               => __( 'Добавить тип сделки', 'realtrigel-core' ),
			'new_item_name'              => __( 'Название нового типа сделки', 'realtrigel-core' ),
			'menu_name'                  => __( 'Справочник типов сделки', 'realtrigel-core' ),
			'popular_items'              => __( 'Популярные типы сделки', 'realtrigel-core' ),
			'separate_items_with_commas' => __( 'Разделяйте типы сделки запятыми', 'realtrigel-core' ),
			'add_or_remove_items'        => __( 'Добавить или удалить типы сделки', 'realtrigel-core' ),
			'choose_from_most_used'      => __( 'Выбрать из часто используемых типов сделки', 'realtrigel-core' ),
			'not_found'                  => __( 'Типы сделки не найдены.', 'realtrigel-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Это справочник, из которого выбирается формат сделки по объекту: аренда, продажа, покупка и другие варианты.', 'realtrigel-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => false,
			'rewrite'           => array(
				'slug'       => 'deal-type',
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
				'content' => '<p>' . esc_html__( 'Это справочник типов сделки. Здесь создаются значения вроде "аренда", "продажа", "покупка", которые потом выбираются в карточке объекта как обязательная классификация.', 'realtrigel-core' ) . '</p>',
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
		echo esc_html__( 'Это справочник типов сделки. Здесь создаются значения вроде "аренда", "продажа" или "покупка". Затем эти значения выбираются в карточке объекта как обязательная классификация.', 'realtrigel-core' );
		echo '</p></div>';
	}
}
