<?php
/**
 * Property location fields metabox and save handlers.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Location_Fields {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'rr_property_location_fields_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'rr_property_location_fields_nonce';

	/**
	 * Supported location levels.
	 *
	 * @var string[]
	 */
	private array $levels = array( 'country', 'region', 'city', 'district' );

	/**
	 * Location manager.
	 *
	 * @var RR_Location_Manager
	 */
	private RR_Location_Manager $location_manager;

	/**
	 * Catalog requirements validator.
	 *
	 * @var RR_Catalog_Requirements_Validator
	 */
	private RR_Catalog_Requirements_Validator $requirements_validator;

	/**
	 * Constructor.
	 *
	 * @param RR_Location_Manager               $location_manager      Location manager service.
	 * @param RR_Catalog_Requirements_Validator $requirements_validator Requirements validator.
	 */
	public function __construct( RR_Location_Manager $location_manager, RR_Catalog_Requirements_Validator $requirements_validator ) {
		$this->location_manager       = $location_manager;
		$this->requirements_validator = $requirements_validator;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'handle_save_post' ), 10, 2 );
	}

	/**
	 * Register metabox.
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		add_meta_box(
			'rr-property-location',
			__( 'Локация', 'realtrigel-core' ),
			array( $this, 'render_metabox' ),
			RR_Property_Post_Type::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function render_metabox( \WP_Post $post ): void {
		$selected_chain = $this->get_selected_chain_by_post( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$this->render_metabox_styles();

		$country_options  = $this->get_level_options( 0, true );
		$region_options   = $this->get_level_options( $selected_chain['country'] );
		$city_options     = $this->get_level_options( $selected_chain['region'] );
		$district_options = $this->get_level_options( $selected_chain['city'] );

		echo '<div class="rr-location-metabox">';
		$this->render_level_fields( 'country', $country_options, $selected_chain['country'] );
		$this->render_level_fields( 'region', $region_options, $selected_chain['region'] );
		$this->render_level_fields( 'city', $city_options, $selected_chain['city'] );
		$this->render_level_fields( 'district', $district_options, $selected_chain['district'] );
		echo '</div>';

		$this->render_metabox_script( $selected_chain );
	}

	/**
	 * Save handler for property posts.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function handle_save_post( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( RR_Property_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$data = $this->requirements_validator->build_consistent_location_data_from_request();

		if ( $this->requirements_validator->is_empty_location_data( $data ) ) {
			wp_set_object_terms( $post_id, array(), RR_Property_Location_Taxonomy::TAXONOMY, false );
			return;
		}

		$terms_by_level = $this->location_manager->get_or_create_location_terms( $data );

		if ( is_wp_error( $terms_by_level ) ) {
			return;
		}

		$term_ids = array_values(
			array_filter(
				$terms_by_level,
				static fn( int $term_id ): bool => $term_id > 0
			)
		);

		wp_set_object_terms( $post_id, $term_ids, RR_Property_Location_Taxonomy::TAXONOMY, false );
	}

	/**
	 * Render inline metabox script for level resets and field sync.
	 *
	 * @param array<string,int> $selected_chain Selected term IDs by level.
	 *
	 * @return void
	 */
	private function render_metabox_script( array $selected_chain ): void {
		$json_flags        = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$children_map_json = wp_json_encode( $this->get_location_children_map(), $json_flags );
		$selected_json     = wp_json_encode(
			array(
				'country'  => (int) $selected_chain['country'],
				'region'   => (int) $selected_chain['region'],
				'city'     => (int) $selected_chain['city'],
				'district' => (int) $selected_chain['district'],
			),
			$json_flags
		);
		$children_map_json = false !== $children_map_json ? $children_map_json : '{}';
		$selected_json     = false !== $selected_json ? $selected_json : '{}';
		?>
		<script>
			(function () {
				const levels = ['country', 'region', 'city', 'district'];
				const childrenMap = <?php echo $children_map_json; ?>;
				const selectedChain = <?php echo $selected_json; ?>;
				const existingPlaceholder = <?php echo wp_json_encode( __( 'Выберите существующее значение', 'realtrigel-core' ) ); ?>;

				const getSelect = (level) => document.getElementById('rr_existing_' + level);
				const getInput = (level) => document.getElementById('rr_new_' + level);
				const getNumericValue = (value) => {
					const parsed = parseInt(String(value || '0'), 10);
					return Number.isNaN(parsed) ? 0 : parsed;
				};
				const setSelectOptions = (level, parentId, selectedId = 0) => {
					const select = getSelect(level);

					if (!select) {
						return;
					}

					const key = String(getNumericValue(parentId));
					const options = (level !== 'country' && getNumericValue(parentId) <= 0)
						? []
						: (Array.isArray(childrenMap[key]) ? childrenMap[key] : []);

					select.innerHTML = '';
					const placeholder = document.createElement('option');
					placeholder.value = '0';
					placeholder.textContent = existingPlaceholder;
					select.appendChild(placeholder);

					options.forEach((item) => {
						const option = document.createElement('option');
						option.value = String(item.id);
						option.textContent = item.name;
						if (getNumericValue(selectedId) === getNumericValue(item.id)) {
							option.selected = true;
						}
						select.appendChild(option);
					});
				};
				const resetLevel = (level) => {
					const select = getSelect(level);
					const input = getInput(level);

					if (select) {
						select.value = '0';
					}

					if (input) {
						input.value = '';
					}
				};

				const resetBelow = (level) => {
					const index = levels.indexOf(level);

					if (index < 0) {
						return;
					}

					for (let i = index + 1; i < levels.length; i++) {
						resetLevel(levels[i]);
					}
				};
				const rebuildFromCountry = () => {
					const countryId = getNumericValue(getSelect('country') ? getSelect('country').value : 0);
					const regionId = getNumericValue(getSelect('region') ? getSelect('region').value : 0);
					const cityId = getNumericValue(getSelect('city') ? getSelect('city').value : 0);

					setSelectOptions('region', countryId, regionId);
					setSelectOptions('city', regionId, cityId);
					setSelectOptions('district', cityId, getSelect('district') ? getSelect('district').value : 0);
				};
				const initializeSelects = () => {
					const countrySelect = getSelect('country');
					if (!countrySelect) {
						return;
					}

					if (getNumericValue(selectedChain.country) > 0) {
						countrySelect.value = String(getNumericValue(selectedChain.country));
					}

					setSelectOptions('region', getNumericValue(countrySelect.value), selectedChain.region || 0);
					setSelectOptions('city', selectedChain.region || 0, selectedChain.city || 0);
					setSelectOptions('district', selectedChain.city || 0, selectedChain.district || 0);
				};

				levels.forEach((level) => {
					const select = getSelect(level);
					const input = getInput(level);

					if (select) {
						select.addEventListener('change', () => {
							if (input && select.value !== '0') {
								input.value = '';
							}

							if (level === 'country') {
								resetBelow(level);
								setSelectOptions('region', getNumericValue(select.value), 0);
								setSelectOptions('city', 0, 0);
								setSelectOptions('district', 0, 0);
							} else if (level === 'region') {
								resetBelow(level);
								setSelectOptions('city', getNumericValue(select.value), 0);
								setSelectOptions('district', 0, 0);
							} else if (level === 'city') {
								resetBelow(level);
								setSelectOptions('district', getNumericValue(select.value), 0);
							}
						});
					}

					if (input) {
						input.addEventListener('input', () => {
							const hasValue = input.value.trim() !== '';

							if (hasValue && select) {
								select.value = '0';
							}

							if (hasValue && level === 'country') {
								resetBelow(level);
								setSelectOptions('region', 0, 0);
								setSelectOptions('city', 0, 0);
								setSelectOptions('district', 0, 0);
							} else if (hasValue && level === 'region') {
								resetBelow(level);
								setSelectOptions('city', 0, 0);
								setSelectOptions('district', 0, 0);
							} else if (hasValue && level === 'city') {
								resetBelow(level);
								setSelectOptions('district', 0, 0);
							}
						});
					}
				});

				initializeSelects();
				rebuildFromCountry();
			})();
		</script>
		<?php
	}

	/**
	 * Build parent => children map for client-side cascading selects.
	 *
	 * @return array<string,array<int,array{id:int,name:string}>>
	 */
	private function get_location_children_map(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => RR_Property_Location_Taxonomy::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$map = array();

		foreach ( $terms as $term ) {
			$parent_key = (string) (int) $term->parent;

			if ( ! isset( $map[ $parent_key ] ) ) {
				$map[ $parent_key ] = array();
			}

			$map[ $parent_key ][] = array(
				'id'   => (int) $term->term_id,
				'name' => $term->name,
			);
		}

		return $map;
	}

	/**
	 * Render select + input pair for location level.
	 *
	 * @param string          $level       Location level.
	 * @param array<int,\WP_Term> $options     Options.
	 * @param int             $selected_id Selected term ID.
	 *
	 * @return void
	 */
	private function render_level_fields( string $level, array $options, int $selected_id ): void {
		$labels = $this->get_level_labels( $level );

		echo '<div class="rr-location-group">';
		echo '<h4>' . esc_html( $labels['title'] ) . '</h4>';
		echo '<div class="rr-location-row">';
		echo '<div class="rr-location-field">';
		echo '<label for="' . esc_attr( 'rr_existing_' . $level ) . '"><strong>' . esc_html( $labels['existing'] ) . '</strong></label>';
		echo '<select id="' . esc_attr( 'rr_existing_' . $level ) . '" name="' . esc_attr( 'rr_location_existing_' . $level ) . '">';
		echo '<option value="0">' . esc_html__( 'Выберите существующее значение', 'realtrigel-core' ) . '</option>';

		foreach ( $options as $term ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $term->term_id,
				selected( $selected_id, (int) $term->term_id, false ),
				esc_html( $term->name )
			);
		}

		echo '</select>';
		echo '</div>';
		echo '<div class="rr-location-field">';
		echo '<label for="' . esc_attr( 'rr_new_' . $level ) . '"><strong>' . esc_html( $labels['new'] ) . '</strong></label>';
		echo '<input id="' . esc_attr( 'rr_new_' . $level ) . '" name="' . esc_attr( 'rr_location_new_' . $level ) . '" type="text" value="" />';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render compact metabox styles.
	 *
	 * @return void
	 */
	private function render_metabox_styles(): void {
		?>
		<style>
			.rr-location-metabox .rr-location-group {
				margin-bottom: 12px;
			}

			.rr-location-metabox .rr-location-group:last-child {
				margin-bottom: 0;
			}

			.rr-location-metabox .rr-location-group h4 {
				margin: 0 0 6px;
				font-size: 13px;
				line-height: 1.3;
			}

			.rr-location-metabox .rr-location-row {
				display: flex;
				flex-wrap: wrap;
				gap: 12px;
			}

			.rr-location-metabox .rr-location-field {
				flex: 1 1 280px;
				min-width: 0;
			}

			.rr-location-metabox .rr-location-field label {
				display: block;
				margin-bottom: 4px;
			}

			.rr-location-metabox .rr-location-field select,
			.rr-location-metabox .rr-location-field input {
				width: 100%;
			}
		</style>
		<?php
	}

	/**
	 * Get translated labels by level.
	 *
	 * @param string $level Location level.
	 *
	 * @return array<string,string>
	 */
	private function get_level_labels( string $level ): array {
		$map = array(
			'country'  => array(
				'title'    => __( 'Страна', 'realtrigel-core' ),
				'existing' => __( 'Существующая страна', 'realtrigel-core' ),
				'new'      => __( 'Новая страна', 'realtrigel-core' ),
			),
			'region'   => array(
				'title'    => __( 'Регион', 'realtrigel-core' ),
				'existing' => __( 'Существующий регион', 'realtrigel-core' ),
				'new'      => __( 'Новый регион', 'realtrigel-core' ),
			),
			'city'     => array(
				'title'    => __( 'Город', 'realtrigel-core' ),
				'existing' => __( 'Существующий город', 'realtrigel-core' ),
				'new'      => __( 'Новый город', 'realtrigel-core' ),
			),
			'district' => array(
				'title'    => __( 'Район', 'realtrigel-core' ),
				'existing' => __( 'Существующий район', 'realtrigel-core' ),
				'new'      => __( 'Новый район', 'realtrigel-core' ),
			),
		);

		if ( isset( $map[ $level ] ) ) {
			return $map[ $level ];
		}

		return array(
			'title'    => __( 'Локация', 'realtrigel-core' ),
			'existing' => __( 'Существующее значение', 'realtrigel-core' ),
			'new'      => __( 'Новое значение', 'realtrigel-core' ),
		);
	}

	/**
	 * Get selected term chain by level for post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<string,int>
	 */
	private function get_selected_chain_by_post( int $post_id ): array {
		$chain = array(
			'country'  => 0,
			'region'   => 0,
			'city'     => 0,
			'district' => 0,
		);

		$terms = wp_get_object_terms(
			$post_id,
			RR_Property_Location_Taxonomy::TAXONOMY,
			array(
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $chain;
		}

		$deepest = $this->get_deepest_term( $terms );
		if ( null === $deepest ) {
			return $chain;
		}

		$ancestor_ids = array_reverse( get_ancestors( $deepest->term_id, RR_Property_Location_Taxonomy::TAXONOMY, 'taxonomy' ) );
		$ordered_ids  = array_merge( $ancestor_ids, array( (int) $deepest->term_id ) );
		$max_levels   = count( $this->levels );
		$level_index  = 0;

		foreach ( $ordered_ids as $term_id ) {
			if ( $level_index >= $max_levels ) {
				break;
			}

			$term = get_term( $term_id, RR_Property_Location_Taxonomy::TAXONOMY );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$chain[ $this->levels[ $level_index ] ] = (int) $term->term_id;
			$level_index++;
		}

		return $chain;
	}

	/**
	 * Pick deepest term from term list.
	 *
	 * @param array<int,\WP_Term> $terms Terms.
	 *
	 * @return \WP_Term|null
	 */
	private function get_deepest_term( array $terms ): ?\WP_Term {
		$selected  = null;
		$max_depth = -1;

		foreach ( $terms as $term ) {
			$depth = count( get_ancestors( $term->term_id, RR_Property_Location_Taxonomy::TAXONOMY, 'taxonomy' ) );
			if ( $depth > $max_depth ) {
				$selected  = $term;
				$max_depth = $depth;
			}
		}

		return $selected;
	}

	/**
	 * Get options for a level by hierarchy.
	 *
	 * @param int  $parent_id     Parent ID for filtered query.
	 * @param bool $include_roots Include top-level terms.
	 *
	 * @return array<int,\WP_Term>
	 */
	private function get_level_options( int $parent_id = 0, bool $include_roots = false ): array {
		if ( ! $include_roots && $parent_id <= 0 ) {
			return array();
		}

		$args = array(
			'taxonomy'   => RR_Property_Location_Taxonomy::TAXONOMY,
			'hide_empty' => false,
			'parent'     => $parent_id,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}
}
