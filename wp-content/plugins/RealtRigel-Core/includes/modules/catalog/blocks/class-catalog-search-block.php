<?php
/**
 * Native Gutenberg block for catalog search form.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Search_Block {

	/**
	 * Absolute path to block metadata directory.
	 */
	private const BLOCK_PATH = __DIR__ . '/catalog-search';

	/**
	 * REST namespace.
	 */
	private const REST_NAMESPACE = 'realtrigel/v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register block metadata and render callback.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK_PATH,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/catalog-location-suggestions',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_location_suggestions' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'required'          => false,
					),
					'parent' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'required'          => false,
					),
				),
			)
		);
	}

	/**
	 * Return location suggestions for autocomplete.
	 *
	 * @param \WP_REST_Request $request REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_location_suggestions( \WP_REST_Request $request ): \WP_REST_Response {
		$search = trim( (string) $request->get_param( 'search' ) );
		$parent = max( 0, (int) $request->get_param( 'parent' ) );

		if ( '' === $search && $parent <= 0 ) {
			return rest_ensure_response( array() );
		}

		$query_args = array(
			'taxonomy'   => RR_Property_Location_Taxonomy::TAXONOMY,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => 200,
		);

		if ( $parent > 0 ) {
			$query_args['parent'] = $parent;
		}

		$terms = get_terms( $query_args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return rest_ensure_response( array() );
		}

		$items = array();
		$needle = $this->normalize_location_search_value( $search );

		foreach ( $terms as $term ) {
			$path = $this->build_term_path( (int) $term->term_id );
			$label = implode( ' / ', wp_list_pluck( $path, 'name' ) );

			if ( '' !== $needle && ! $this->location_matches_search( $term, $label, $needle ) ) {
				continue;
			}

			$items[] = array(
				'id'        => (int) $term->term_id,
				'parent_id' => (int) $term->parent,
				'slug'      => (string) $term->slug,
				'name'      => (string) $term->name,
				'label'     => $label,
				'path'      => $path,
				'has_child' => $this->term_has_children( (int) $term->term_id ),
			);

			if ( count( $items ) >= 12 ) {
				break;
			}
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Normalize search value for location matching.
	 *
	 * @param string $value Raw search value.
	 *
	 * @return string
	 */
	private function normalize_location_search_value( string $value ): string {
		$value = remove_accents( $value );
		$value = mb_strtolower( $value );

		return trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' );
	}

	/**
	 * Determine whether location term matches current search value.
	 *
	 * @param \WP_Term $term   Term object.
	 * @param string   $label  Full path label.
	 * @param string   $needle Normalized needle.
	 *
	 * @return bool
	 */
	private function location_matches_search( \WP_Term $term, string $label, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		$haystacks = array(
			$this->normalize_location_search_value( (string) $term->name ),
			$this->normalize_location_search_value( (string) $term->slug ),
			$this->normalize_location_search_value( $label ),
		);

		foreach ( $haystacks as $haystack ) {
			if ( '' !== $haystack && false !== mb_strpos( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render block markup.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		$title              = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
		$button_label       = isset( $attributes['buttonLabel'] ) ? trim( (string) $attributes['buttonLabel'] ) : '';
		$target_url         = isset( $attributes['targetUrl'] ) ? trim( (string) $attributes['targetUrl'] ) : '';
		$show_reset         = ! empty( $attributes['showResetButton'] );
		$more_filters_label = isset( $attributes['moreFiltersLabel'] ) ? trim( (string) $attributes['moreFiltersLabel'] ) : '';
		$less_filters_label = isset( $attributes['lessFiltersLabel'] ) ? trim( (string) $attributes['lessFiltersLabel'] ) : '';
		$location_label     = isset( $attributes['searchPlaceholder'] ) ? trim( (string) $attributes['searchPlaceholder'] ) : '';
		$filters            = RR_Catalog_Properties_Block::get_request_filters();
		$selected_locations = $this->get_selected_locations_state( $filters['locations'] );
		$location_json      = wp_json_encode( $selected_locations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$location_json      = false !== $location_json ? $location_json : '[]';
		$action_url         = '' !== $target_url ? esc_url_raw( $target_url ) : $this->get_current_url();
		$total_objects      = wp_count_posts( RR_Property_Post_Type::POST_TYPE );
		$total_objects      = $total_objects instanceof \stdClass ? (int) $total_objects->publish : 0;
		$has_extra_active   = '' !== $filters['area_min'] || '' !== $filters['area_max'];

		if ( '' === $title ) {
			$title = __( 'Поиск недвижимости', 'realtrigel-core' );
		}

		if ( '' === $button_label ) {
			$button_label = __( 'Поиск', 'realtrigel-core' );
		}

		if ( '' === $more_filters_label ) {
			$more_filters_label = __( 'Больше фильтров', 'realtrigel-core' );
		}

		if ( '' === $less_filters_label ) {
			$less_filters_label = __( 'Меньше фильтров', 'realtrigel-core' );
		}

		if ( '' === $location_label ) {
			$location_label = __( 'Начните вводить локацию', 'realtrigel-core' );
		}

		$search_icon_url = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'search', 'thumbnail' ) : '';

		ob_start();
		?>
		<section
			class="rr-catalog-search-block"
			data-search-icon-url="<?php echo esc_attr( $search_icon_url ); ?>"
			data-open-search-label="<?php echo esc_attr__( 'Открыть поиск', 'realtrigel-core' ); ?>"
			data-close-search-label="<?php echo esc_attr__( 'Закрыть поиск', 'realtrigel-core' ); ?>"
		>
			<div class="rr-catalog-search-block__inner">
				<?php if ( '' !== $title ) : ?>
					<header class="rr-catalog-search-block__header">
						<h2 class="rr-catalog-search-block__title"><?php echo esc_html( $title ); ?></h2>
					</header>
				<?php endif; ?>

				<form class="rr-catalog-search-form" method="get" action="<?php echo esc_url( $action_url ); ?>" data-rr-search-form>
					<div class="rr-catalog-search-form__top">
						<div
							class="rr-catalog-search-form__search rr-catalog-search-form__search--location"
							data-rr-location-autocomplete
							data-endpoint="<?php echo esc_url( rest_url( self::REST_NAMESPACE . '/catalog-location-suggestions' ) ); ?>"
							data-initial-selections="<?php echo esc_attr( $location_json ); ?>"
							data-remove-location-label="<?php echo esc_attr__( 'Удалить локацию', 'realtrigel-core' ); ?>"
							data-empty-results-label="<?php echo esc_attr__( 'Ничего не найдено', 'realtrigel-core' ); ?>"
						>
							<input type="hidden" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_SEARCH ); ?>" value="<?php echo esc_attr( $filters['search'] ); ?>" />
							<div data-rr-location-hidden-inputs>
								<?php foreach ( $selected_locations as $selected_location ) : ?>
									<input type="hidden" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_LOCATION ); ?>[]" value="<?php echo esc_attr( $selected_location['slug'] ); ?>" />
								<?php endforeach; ?>
							</div>
							<div class="rr-location-search__control">
								<span class="rr-catalog-search-form__search-icon" aria-hidden="true">
									<?php if ( '' !== $search_icon_url ) : ?>
										<img src="<?php echo esc_url( $search_icon_url ); ?>" alt="" />
									<?php else : ?>
										⌕
									<?php endif; ?>
								</span>
								<div class="rr-location-search__selected" data-rr-location-selected></div>
								<input
									id="rr-catalog-location-input"
									type="text"
									autocomplete="off"
									placeholder="<?php echo esc_attr( $location_label ); ?>"
									value=""
									data-rr-location-input
								/>
								<button type="button" class="rr-location-search__clear" data-rr-location-clear aria-label="<?php esc_attr_e( 'Очистить локацию', 'realtrigel-core' ); ?>" hidden>&times;</button>
							</div>
							<div class="rr-location-search__results" data-rr-location-results hidden></div>
							<?php if ( $total_objects > 0 ) : ?>
								<span class="rr-catalog-search-form__count">
									<?php
									printf(
										/* translators: %d: properties count. */
										esc_html__( '%d объектов', 'realtrigel-core' ),
										$total_objects
									);
									?>
								</span>
							<?php endif; ?>
						</div>

						<div class="rr-catalog-search-form__top-actions">
							<button type="submit" class="rr-catalog-search-form__submit">
								<?php echo esc_html( $button_label ); ?>
							</button>

							<?php if ( $show_reset ) : ?>
								<a class="rr-catalog-search-form__reset" href="<?php echo esc_url( $action_url ); ?>">
									<?php esc_html_e( 'Сбросить', 'realtrigel-core' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="rr-catalog-search-form__primary">
						<div class="rr-catalog-search-form__field">
							<label for="rr-catalog-deal-type"><?php esc_html_e( 'Тип сделки', 'realtrigel-core' ); ?></label>
							<select id="rr-catalog-deal-type" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_DEAL_TYPE ); ?>">
								<option value=""><?php esc_html_e( 'Любой тип сделки', 'realtrigel-core' ); ?></option>
								<?php foreach ( $this->get_term_options( RR_Property_Deal_Type_Taxonomy::TAXONOMY ) as $option ) : ?>
									<option value="<?php echo esc_attr( $option['slug'] ); ?>" <?php selected( $filters['deal_type'], $option['slug'] ); ?>>
										<?php echo esc_html( $option['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="rr-catalog-search-form__field">
							<label for="rr-catalog-property-type"><?php esc_html_e( 'Тип объекта', 'realtrigel-core' ); ?></label>
							<select id="rr-catalog-property-type" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_PROPERTY_TYPE ); ?>">
								<option value=""><?php esc_html_e( 'Любой тип объекта', 'realtrigel-core' ); ?></option>
								<?php foreach ( $this->get_term_options( RR_Property_Type_Taxonomy::TAXONOMY ) as $option ) : ?>
									<option value="<?php echo esc_attr( $option['slug'] ); ?>" <?php selected( $filters['property_type'], $option['slug'] ); ?>>
										<?php echo esc_html( $option['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="rr-catalog-search-form__field">
							<label for="rr-catalog-price-min"><?php esc_html_e( 'Цена от', 'realtrigel-core' ); ?></label>
							<input id="rr-catalog-price-min" type="number" min="0" step="1" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_PRICE_MIN ); ?>" value="<?php echo esc_attr( $filters['price_min'] ); ?>" />
						</div>

						<div class="rr-catalog-search-form__field">
							<label for="rr-catalog-price-max"><?php esc_html_e( 'Цена до', 'realtrigel-core' ); ?></label>
							<input id="rr-catalog-price-max" type="number" min="0" step="1" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_PRICE_MAX ); ?>" value="<?php echo esc_attr( $filters['price_max'] ); ?>" />
						</div>
					</div>

					<div class="rr-catalog-search-form__extra" data-rr-extra-filters data-expanded="<?php echo $has_extra_active ? '1' : '0'; ?>">
						<div class="rr-catalog-search-form__divider"></div>

						<div class="rr-catalog-search-form__extra-grid" <?php echo $has_extra_active ? '' : 'hidden'; ?>>
							<div class="rr-catalog-search-form__field">
								<label for="rr-catalog-area-min"><?php esc_html_e( 'Площадь от', 'realtrigel-core' ); ?></label>
								<input id="rr-catalog-area-min" type="number" min="0" step="0.01" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_AREA_MIN ); ?>" value="<?php echo esc_attr( $filters['area_min'] ); ?>" />
							</div>

							<div class="rr-catalog-search-form__field">
								<label for="rr-catalog-area-max"><?php esc_html_e( 'Площадь до', 'realtrigel-core' ); ?></label>
								<input id="rr-catalog-area-max" type="number" min="0" step="0.01" name="<?php echo esc_attr( RR_Catalog_Properties_Block::FILTER_AREA_MAX ); ?>" value="<?php echo esc_attr( $filters['area_max'] ); ?>" />
							</div>
						</div>

						<button
							type="button"
							class="rr-catalog-search-form__toggle"
							data-rr-extra-toggle
							data-more-label="<?php echo esc_attr( $more_filters_label ); ?>"
							data-less-label="<?php echo esc_attr( $less_filters_label ); ?>"
						>
							<?php echo esc_html( $has_extra_active ? $less_filters_label : $more_filters_label ); ?>
						</button>
					</div>
				</form>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Get selected location state list from slugs.
	 *
	 * @param array<int,string> $slugs Selected location slugs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_selected_locations_state( array $slugs ): array {
		$items = array();

		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, RR_Property_Location_Taxonomy::TAXONOMY );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$path = $this->build_term_path( (int) $term->term_id );

			$items[] = array(
				'id'    => (int) $term->term_id,
				'slug'  => (string) $term->slug,
				'name'  => (string) $term->name,
				'label' => implode( ' / ', wp_list_pluck( $path, 'name' ) ),
				'path'  => $path,
			);
		}

		return $items;
	}

	/**
	 * Build path for selected term.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function build_term_path( int $term_id ): array {
		$path         = array();
		$ancestor_ids = array_reverse( get_ancestors( $term_id, RR_Property_Location_Taxonomy::TAXONOMY, 'taxonomy' ) );
		$term_ids     = array_merge( $ancestor_ids, array( $term_id ) );

		foreach ( $term_ids as $current_term_id ) {
			$term = get_term( $current_term_id, RR_Property_Location_Taxonomy::TAXONOMY );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$path[] = array(
				'id'   => (int) $term->term_id,
				'slug' => (string) $term->slug,
				'name' => (string) $term->name,
			);
		}

		return $path;
	}

	/**
	 * Determine whether term has children.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return bool
	 */
	private function term_has_children( int $term_id ): bool {
		$children = get_terms(
			array(
				'taxonomy'   => RR_Property_Location_Taxonomy::TAXONOMY,
				'hide_empty' => false,
				'parent'     => $term_id,
				'number'     => 1,
				'fields'     => 'ids',
			)
		);

		return is_array( $children ) && ! empty( $children );
	}

	/**
	 * Get term options for select field.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_term_options( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			$options[] = array(
				'slug'  => (string) $term->slug,
				'label' => (string) $term->name,
			);
		}

		return $options;
	}

	/**
	 * Resolve current request URL without query string.
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		if ( is_singular() ) {
			$permalink = get_permalink( get_queried_object_id() );

			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		global $wp;

		if ( isset( $wp->request ) && '' !== (string) $wp->request ) {
			return home_url( user_trailingslashit( (string) $wp->request ) );
		}

		return home_url( '/' );
	}
}
