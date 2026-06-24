<?php
/**
 * Property type taxonomy registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Type_Taxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'property_type';

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
			'name'                       => __( 'Справочник типов недвижимости', 'realtrigel-core' ),
			'singular_name'              => __( 'Тип недвижимости', 'realtrigel-core' ),
			'search_items'               => __( 'Искать типы недвижимости', 'realtrigel-core' ),
			'all_items'                  => __( 'Все типы недвижимости', 'realtrigel-core' ),
			'parent_item'                => __( 'Родительский тип недвижимости', 'realtrigel-core' ),
			'parent_item_colon'          => __( 'Родительский тип недвижимости:', 'realtrigel-core' ),
			'edit_item'                  => __( 'Редактировать тип недвижимости', 'realtrigel-core' ),
			'update_item'                => __( 'Обновить тип недвижимости', 'realtrigel-core' ),
			'add_new_item'               => __( 'Добавить тип недвижимости', 'realtrigel-core' ),
			'new_item_name'              => __( 'Название нового типа недвижимости', 'realtrigel-core' ),
			'menu_name'                  => __( 'Справочник типов недвижимости', 'realtrigel-core' ),
			'popular_items'              => __( 'Популярные типы недвижимости', 'realtrigel-core' ),
			'separate_items_with_commas' => __( 'Разделяйте типы недвижимости запятыми', 'realtrigel-core' ),
			'add_or_remove_items'        => __( 'Добавить или удалить типы недвижимости', 'realtrigel-core' ),
			'choose_from_most_used'      => __( 'Выбрать из часто используемых типов недвижимости', 'realtrigel-core' ),
			'not_found'                  => __( 'Типы недвижимости не найдены.', 'realtrigel-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Это справочник, из которого выбирается тип объекта в карточке недвижимости: квартира, офис, участок, дом и т.д.', 'realtrigel-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => false,
			'rewrite'           => array(
				'slug'       => 'property-type',
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
				'content' => '<p>' . esc_html__( 'Это справочник типов недвижимости. Здесь создаются значения вроде "квартира", "офис", "дом", "участок", которые потом выбираются в карточке объекта как обязательная классификация.', 'realtrigel-core' ) . '</p>',
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
		echo esc_html__( 'Это справочник типов недвижимости. Здесь создаются значения вроде "квартира", "офис", "дом" или "участок". Затем эти значения выбираются в карточке объекта как обязательная классификация.', 'realtrigel-core' );
		echo '</p></div>';
	}
}
