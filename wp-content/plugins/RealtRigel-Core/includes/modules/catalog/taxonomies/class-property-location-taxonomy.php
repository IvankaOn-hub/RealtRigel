<?php
/**
 * Property location taxonomy registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Location_Taxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'property_location';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'created_' . self::TAXONOMY, array( $this, 'sync_location_level' ), 10, 1 );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'sync_location_level' ), 10, 1 );
		add_action( 'current_screen', array( $this, 'add_admin_help' ) );
	}

	/**
	 * Register taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'              => __( 'Справочник локаций объектов', 'realtrigel-core' ),
			'singular_name'     => __( 'Локация объекта', 'realtrigel-core' ),
			'search_items'      => __( 'Искать локации', 'realtrigel-core' ),
			'all_items'         => __( 'Все локации', 'realtrigel-core' ),
			'parent_item'       => __( 'Родительская локация', 'realtrigel-core' ),
			'parent_item_colon' => __( 'Родительская локация:', 'realtrigel-core' ),
			'edit_item'         => __( 'Редактировать локацию', 'realtrigel-core' ),
			'update_item'       => __( 'Обновить локацию', 'realtrigel-core' ),
			'add_new_item'      => __( 'Добавить локацию', 'realtrigel-core' ),
			'new_item_name'     => __( 'Новая локация', 'realtrigel-core' ),
			'menu_name'         => __( 'Справочник локаций', 'realtrigel-core' ),
		);

		$args = array(
			'labels'       => $labels,
			'description'  => __( 'Это справочник географии объектов. Здесь создаются страны, регионы, города и районы, которые потом выбираются в карточке объекта.', 'realtrigel-core' ),
			'hierarchical' => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'public'       => true,
			'rewrite'      => array(
				'slug'         => 'location',
				'with_front'   => false,
				'hierarchical' => true,
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
				'content' => '<p>' . esc_html__( 'Это справочник локаций для каталога. Здесь создаются страны, регионы, города и районы, которые потом выбираются у объекта недвижимости и используются в адресе и фильтрации.', 'realtrigel-core' ) . '</p>',
			)
		);
	}

	/**
	 * Sync location level meta for manually created/edited terms.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return void
	 */
	public function sync_location_level( int $term_id ): void {
		$term = get_term( $term_id, self::TAXONOMY );

		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$depth = count( get_ancestors( $term_id, self::TAXONOMY, 'taxonomy' ) );
		$level = $this->resolve_level_by_depth( $depth );

		update_term_meta( $term_id, 'location_level', $level );
		$this->sync_descendant_levels( $term_id, $depth );
	}

	/**
	 * Resolve location level by taxonomy depth.
	 *
	 * @param int $depth Hierarchy depth.
	 *
	 * @return string
	 */
	private function resolve_level_by_depth( int $depth ): string {
		if ( $depth <= 0 ) {
			return 'country';
		}

		if ( 1 === $depth ) {
			return 'region';
		}

		if ( 2 === $depth ) {
			return 'city';
		}

		return 'district';
	}

	/**
	 * Sync location_level for descendants after parent changes.
	 *
	 * @param int $parent_id    Parent term ID.
	 * @param int $parent_depth Parent depth.
	 *
	 * @return void
	 */
	private function sync_descendant_levels( int $parent_id, int $parent_depth ): void {
		$children = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => $parent_id,
			)
		);

		if ( is_wp_error( $children ) || empty( $children ) ) {
			return;
		}

		foreach ( $children as $child ) {
			$child_depth = $parent_depth + 1;
			$child_level = $this->resolve_level_by_depth( $child_depth );

			update_term_meta( (int) $child->term_id, 'location_level', $child_level );
			$this->sync_descendant_levels( (int) $child->term_id, $child_depth );
		}
	}
}
