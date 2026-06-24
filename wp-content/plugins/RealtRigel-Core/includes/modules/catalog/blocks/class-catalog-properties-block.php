<?php
/**
 * Native Gutenberg block for catalog properties listing.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Properties_Block {

	/**
	 * Shared query parameter names.
	 */
	public const FILTER_LOCATION      = 'catalog_location';
	public const FILTER_DEAL_TYPE     = 'catalog_deal_type';
	public const FILTER_PROPERTY_TYPE = 'catalog_property_type';
	public const FILTER_SEARCH        = 'catalog_search';
	public const FILTER_PRICE_MIN     = 'catalog_price_min';
	public const FILTER_PRICE_MAX     = 'catalog_price_max';
	public const FILTER_AREA_MIN      = 'catalog_area_min';
	public const FILTER_AREA_MAX      = 'catalog_area_max';
	public const FILTER_SORT          = 'catalog_sort';

	/**
	 * Absolute path to block metadata directory.
	 */
	private const BLOCK_PATH = __DIR__ . '/catalog-properties';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
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
	 * Render block markup.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render( array $attributes ): string {
		$items_to_show  = isset( $attributes['itemsToShow'] ) ? max( 1, (int) $attributes['itemsToShow'] ) : 6;
		$legacy_columns = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
		$show_excerpt   = ! empty( $attributes['showExcerpt'] );
		$show_title     = ! empty( $attributes['showTitle'] );
		$order_by       = isset( $attributes['orderBy'] ) ? (string) $attributes['orderBy'] : 'date';
		$media_mode     = isset( $attributes['mediaMode'] ) ? (string) $attributes['mediaMode'] : 'featured';
		$block_title    = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
		$button_label   = isset( $attributes['buttonLabel'] ) ? trim( (string) $attributes['buttonLabel'] ) : '';
		$block_id       = isset( $attributes['blockId'] ) ? sanitize_key( (string) $attributes['blockId'] ) : '';
		$navigation     = isset( $attributes['navigationMode'] ) ? (string) $attributes['navigationMode'] : 'none';
		$layout_rules   = $this->normalize_layout_rules( $attributes, $legacy_columns );

		if ( '' === $button_label ) {
			$button_label = __( 'Смотреть объект', 'realtrigel-core' );
		}

		if ( '' === $block_id ) {
			$block_id = 'properties';
		}

		$allowed_order_by = array( 'date', 'title', 'menu_order' );
		$allowed_media    = array( 'featured', 'gallery' );
		$allowed_nav      = array( 'none', 'pagination', 'infinite' );
		if ( ! in_array( $order_by, $allowed_order_by, true ) ) {
			$order_by = 'date';
		}

		if ( ! in_array( $media_mode, $allowed_media, true ) ) {
			$media_mode = 'featured';
		}

		if ( ! in_array( $navigation, $allowed_nav, true ) ) {
			$navigation = 'none';
		}

		$page_query_var = 'rrcp_' . $block_id . '_page';
		$current_page   = isset( $_GET[ $page_query_var ] ) ? max( 1, absint( wp_unslash( $_GET[ $page_query_var ] ) ) ) : 1;

		$query_args = array(
			'post_type'           => RR_Property_Post_Type::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $items_to_show,
			'paged'               => $current_page,
			'orderby'             => $order_by,
			'order'               => 'title' === $order_by ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => 'none' === $navigation,
		);

		$query_args = $this->apply_request_filters_to_query_args( $query_args );
		$query      = new \WP_Query( $query_args );

		$next_page_url = '';
		if ( $query->max_num_pages > $current_page ) {
			$next_page_url = $this->build_page_url( $page_query_var, $current_page + 1 );
		}

		$filters = self::get_request_filters();

		ob_start();
		?>
		<style>
			.rr-properties-block[data-rr-properties-block-id="<?php echo esc_attr( $block_id ); ?>"] .rr-properties-block__grid {
				grid-template-columns: 1fr;
			}
			<?php foreach ( $layout_rules as $layout_rule ) : ?>
				@media (min-width: <?php echo (int) $layout_rule['minWidth']; ?>px) {
					.rr-properties-block[data-rr-properties-block-id="<?php echo esc_attr( $block_id ); ?>"] .rr-properties-block__grid {
						grid-template-columns: repeat(<?php echo (int) $layout_rule['columns']; ?>, minmax(0, 1fr));
					}
				}
			<?php endforeach; ?>
		</style>
		<section
			class="rr-properties-block"
			data-rr-properties-block
			data-rr-properties-block-id="<?php echo esc_attr( $block_id ); ?>"
			data-navigation-mode="<?php echo esc_attr( $navigation ); ?>"
		>
			<?php if ( $show_title && '' !== $block_title ) : ?>
				<header class="rr-properties-block__header">
					<h2 class="rr-properties-block__title"><?php echo esc_html( $block_title ); ?></h2>
				</header>
			<?php endif; ?>

			<div class="rr-properties-block__toolbar">
				<?php if ( $query->have_posts() ) : ?>
					<p class="rr-properties-block__count">
						<?php
						printf(
							/* translators: %d: found properties count. */
							esc_html__( 'Найдено %d объектов', 'realtrigel-core' ),
							(int) $query->found_posts
						);
						?>
					</p>
				<?php endif; ?>

				<form class="rr-properties-block__sort" method="get" action="<?php echo esc_url( $this->get_current_url() ); ?>">
					<?php $this->render_persisted_filters_inputs( $filters ); ?>
					<label for="rr-catalog-sort-<?php echo esc_attr( $block_id ); ?>"><?php esc_html_e( 'Сортировка', 'realtrigel-core' ); ?></label>
					<select id="rr-catalog-sort-<?php echo esc_attr( $block_id ); ?>" name="<?php echo esc_attr( self::FILTER_SORT ); ?>">
						<?php foreach ( $this->get_sort_options() as $sort_value => $sort_label ) : ?>
							<option value="<?php echo esc_attr( $sort_value ); ?>" <?php selected( $filters['sort'], $sort_value ); ?>>
								<?php echo esc_html( $sort_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>
			</div>

			<?php if ( $query->have_posts() ) : ?>
				<div class="rr-properties-block__grid" data-rr-properties-grid>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$gallery_images = $this->get_post_gallery_images( get_the_ID() );
						$card_images    = 'gallery' === $media_mode ? $gallery_images : array_slice( $gallery_images, 0, 1 );
						$card_price     = function_exists( 'get_field' ) ? get_field( 'price', get_the_ID() ) : '';
						$card_area      = function_exists( 'get_field' ) ? get_field( 'area', get_the_ID() ) : '';
						$card_floor     = function_exists( 'get_field' ) ? get_field( 'floor', get_the_ID() ) : '';
						$card_currency  = function_exists( 'get_field' ) ? get_field( 'currency', get_the_ID() ) : '';
						$card_types     = get_the_terms( get_the_ID(), RR_Property_Type_Taxonomy::TAXONOMY );
						$card_deals     = get_the_terms( get_the_ID(), RR_Property_Deal_Type_Taxonomy::TAXONOMY );
						$card_locations = get_the_terms( get_the_ID(), RR_Property_Location_Taxonomy::TAXONOMY );
						$card_price     = is_numeric( $card_price ) ? (float) $card_price : 0.0;
						$card_area      = is_numeric( $card_area ) ? (float) $card_area : 0.0;
						$card_floor     = is_numeric( $card_floor ) ? (int) $card_floor : null;
						$card_currency  = is_string( $card_currency ) && '' !== $card_currency ? strtoupper( $card_currency ) : 'USD';
$card_price_label = $card_price > 0 ? number_format( $card_price, 0, '.', '' ) . ' ' . $card_currency : __( 'Договорная', 'realtrigel-core' );
$card_price_markup = $card_price_label;
$card_area_label  = $card_area > 0 ? sprintf(
	/* translators: %s: property area value. */
	__( '%s м²', 'realtrigel-core' ),
	number_format_i18n( $card_area, 1 )
) : '';
$card_floor_label = null !== $card_floor ? number_format_i18n( $card_floor, 0 ) : '';
$card_rate_label  = $card_price > 0 && $card_area > 0 ? sprintf(
	/* translators: 1: approximate price per square meter, 2: currency code. */
	__( '≈ %1$s %2$s / м²', 'realtrigel-core' ),
	number_format( $card_price / $card_area, 0, '.', ' ' ),
	$card_currency
) : '';

if ( preg_match( '/\d+/', $card_price_label, $card_price_match, PREG_OFFSET_CAPTURE ) ) {
$price_digits = (string) $card_price_match[0][0];
$price_offset = (int) $card_price_match[0][1];
$price_prefix = substr( $card_price_label, 0, $price_offset );
$price_suffix = ltrim( substr( $card_price_label, $price_offset + strlen( $price_digits ) ) );
$price_groups = array();

while ( strlen( $price_digits ) > 3 ) {
array_unshift( $price_groups, substr( $price_digits, -3 ) );
$price_digits = substr( $price_digits, 0, -3 );
}

if ( '' !== $price_digits ) {
array_unshift( $price_groups, $price_digits );
}

if ( '' !== $price_suffix ) {
$price_groups[] = $price_suffix;
}

$card_price_markup = $price_prefix . implode( '&nbsp;', $price_groups );
}
						$card_type_label  = is_array( $card_types ) && ! empty( $card_types ) ? (string) $card_types[0]->name : '';
						$card_deal_label  = is_array( $card_deals ) && ! empty( $card_deals ) ? (string) $card_deals[0]->name : '';
						$card_location    = '';
						$location_icon_url = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'location', 'thumbnail' ) : '';
						$area_icon_url     = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'area', 'thumbnail' ) : '';
						$floor_icon_url    = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'floor', 'thumbnail' ) : '';

						if ( is_array( $card_locations ) && ! empty( $card_locations ) ) {
							$deepest_location = null;
							$deepest_depth    = -1;

							foreach ( $card_locations as $card_location_term ) {
								$ancestors = get_ancestors( $card_location_term->term_id, RR_Property_Location_Taxonomy::TAXONOMY, 'taxonomy' );
								$depth     = is_array( $ancestors ) ? count( $ancestors ) : 0;

								if ( $depth > $deepest_depth ) {
									$deepest_depth    = $depth;
									$deepest_location = $card_location_term;
								}
							}

							if ( $deepest_location instanceof WP_Term ) {
								$location_names = array();
								$ancestor_ids   = get_ancestors( $deepest_location->term_id, RR_Property_Location_Taxonomy::TAXONOMY, 'taxonomy' );

								if ( is_array( $ancestor_ids ) ) {
									$ancestor_ids = array_reverse( $ancestor_ids );

									foreach ( $ancestor_ids as $ancestor_id ) {
										$ancestor_term = get_term( $ancestor_id, RR_Property_Location_Taxonomy::TAXONOMY );

										if ( $ancestor_term instanceof WP_Term ) {
											$location_names[] = $ancestor_term->name;
										}
									}
								}

								$location_names[] = $deepest_location->name;

								if ( count( $location_names ) >= 2 ) {
									$card_location = $location_names[ count( $location_names ) - 2 ] . '. ' . $location_names[ count( $location_names ) - 1 ];
								} else {
									$card_location = (string) $deepest_location->name;
								}
							}
						}
						?>
						<article <?php post_class( 'rr-properties-card' ); ?>>
							<a class="rr-properties-card__media<?php echo count( $card_images ) > 1 ? ' has-gallery' : ''; ?>" href="<?php the_permalink(); ?>">
								<?php if ( ! empty( $card_images ) ) : ?>
									<?php if ( count( $card_images ) > 1 ) : ?>
										<div class="rr-properties-card__gallery">
											<div class="rr-properties-card__gallery-main">
												<?php echo wp_kses_post( $card_images[0] ); ?>
											</div>
											<div class="rr-properties-card__gallery-thumbs">
												<?php foreach ( array_slice( $card_images, 1, 3 ) as $image_html ) : ?>
													<span class="rr-properties-card__gallery-thumb"><?php echo wp_kses_post( $image_html ); ?></span>
												<?php endforeach; ?>
											</div>
										</div>
									<?php else : ?>
										<?php echo wp_kses_post( $card_images[0] ); ?>
									<?php endif; ?>
								<?php else : ?>
									<span><?php esc_html_e( 'Изображение объекта', 'realtrigel-core' ); ?></span>
								<?php endif; ?>
							</a>

							<div class="rr-properties-card__content">
								<h3 class="rr-properties-card__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>

								<?php if ( '' !== $card_location ) : ?>
									<p class="rr-properties-card__location">
										<?php if ( '' !== $location_icon_url ) : ?>
											<img class="rr-properties-card__icon rr-properties-card__icon--location" src="<?php echo esc_url( $location_icon_url ); ?>" alt="" />
										<?php endif; ?>
										<span><?php echo esc_html( $card_location ); ?></span>
									</p>
								<?php endif; ?>

								<?php if ( '' !== $card_area_label || '' !== $card_price_label ) : ?>
									<div
										class="rr-properties-card__footer"
										data-rr-card-price-box
										data-base-price="<?php echo esc_attr( (string) $card_price ); ?>"
										data-base-area="<?php echo esc_attr( (string) $card_area ); ?>"
										data-base-currency="<?php echo esc_attr( $card_currency ); ?>"
										data-nbp-api-base="https://api.nbp.pl/api"
										data-rate-unit-label="<?php echo esc_attr__( 'м²', 'realtrigel-core' ); ?>"
									>
										<div class="rr-properties-card__meta">
											<div class="rr-properties-card__tags">
												<?php if ( '' !== $card_type_label ) : ?>
													<span class="rr-properties-card__tag"><?php echo esc_html( $card_type_label ); ?></span>
												<?php endif; ?>
												<?php if ( '' !== $card_deal_label ) : ?>
													<span class="rr-properties-card__tag"><?php echo esc_html( $card_deal_label ); ?></span>
												<?php endif; ?>
											</div>

											<div class="rr-properties-card__details">
												<div class="rr-properties-card__detail-row">
													<div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
														<?php if ( '' !== $card_area_label ) : ?>
															<span class="rr-properties-card__stat">
																<?php if ( '' !== $area_icon_url ) : ?>
																	<img class="rr-properties-card__icon rr-properties-card__icon--stat" src="<?php echo esc_url( $area_icon_url ); ?>" alt="" />
																<?php endif; ?>
																<span><?php echo esc_html( $card_area_label ); ?></span>
															</span>
														<?php endif; ?>
													</div>
													<div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
														<p class="rr-properties-card__price<?php echo $card_price <= 0 ? ' is-negotiable' : ''; ?>" data-rr-card-price-value><?php echo wp_kses_post( $card_price_markup ); ?></p>
													</div>
												</div>

												<div class="rr-properties-card__detail-row">
													<div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--stats">
														<?php if ( '' !== $card_floor_label ) : ?>
															<span class="rr-properties-card__stat rr-properties-card__stat--floor">
																<?php if ( '' !== $floor_icon_url ) : ?>
																	<img class="rr-properties-card__icon rr-properties-card__icon--stat" src="<?php echo esc_url( $floor_icon_url ); ?>" alt="" />
																<?php endif; ?>
																<span><?php echo esc_html( $card_floor_label ); ?></span>
															</span>
														<?php endif; ?>
													</div>
													<div class="rr-properties-card__detail-cell rr-properties-card__detail-cell--price">
														<?php if ( '' !== $card_rate_label ) : ?>
															<span class="rr-properties-card__stat" data-rr-card-rate-value><?php echo esc_html( $card_rate_label ); ?></span>
														<?php endif; ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>

								<?php if ( $show_excerpt ) : ?>
									<div class="rr-properties-card__excerpt">
										<?php echo esc_html( get_the_excerpt() ?: wp_trim_words( wp_strip_all_tags( get_the_content() ), 20 ) ); ?>
									</div>
								<?php endif; ?>

								<a class="rr-properties-card__link" href="<?php the_permalink(); ?>">
									<?php echo esc_html( $button_label ); ?>
								</a>
							</div>
						</article>
						<?php
					endwhile;
					?>
				</div>

				<?php if ( 'pagination' === $navigation && $query->max_num_pages > 1 ) : ?>
					<?php $links = $this->build_pagination_links( $page_query_var, $current_page, (int) $query->max_num_pages ); ?>
					<?php if ( ! empty( $links ) ) : ?>
						<nav class="rr-properties-block__pagination" aria-label="<?php esc_attr_e( 'Навигация по объектам', 'realtrigel-core' ); ?>">
							<ul class="rr-properties-block__pagination-list">
								<?php foreach ( $links as $link ) : ?>
									<li class="rr-properties-block__pagination-item"><?php echo wp_kses_post( $link ); ?></li>
								<?php endforeach; ?>
							</ul>
						</nav>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( 'infinite' === $navigation && '' !== $next_page_url ) : ?>
					<div
						class="rr-properties-block__load-more"
						data-rr-properties-load-more
						data-next-page-url="<?php echo esc_url( $next_page_url ); ?>"
					>
						<button type="button" class="rr-properties-block__load-more-button" data-rr-properties-load-more-button>
							<?php esc_html_e( 'Загрузить еще', 'realtrigel-core' ); ?>
						</button>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<p class="rr-properties-block__empty"><?php esc_html_e( 'Объекты не найдены.', 'realtrigel-core' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Normalize responsive layout rules for the grid.
	 *
	 * @param array<string,mixed> $attributes      Block attributes.
	 * @param int                 $legacy_columns  Fallback desktop columns.
	 *
	 * @return array<int,array{minWidth:int,columns:int}>
	 */
	private function normalize_layout_rules( array $attributes, int $legacy_columns ): array {
		$rules = array();

		if ( isset( $attributes['layoutRules'] ) && is_array( $attributes['layoutRules'] ) ) {
			foreach ( $attributes['layoutRules'] as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}

				$min_width = isset( $rule['minWidth'] ) ? max( 320, (int) $rule['minWidth'] ) : 320;
				$columns   = isset( $rule['columns'] ) ? max( 1, min( 4, (int) $rule['columns'] ) ) : 1;

				$rules[] = array(
					'minWidth' => $min_width,
					'columns'  => $columns,
				);
			}
		}

		if ( empty( $rules ) ) {
			$columns_tablet     = isset( $attributes['columnsTablet'] ) ? max( 1, min( 4, (int) $attributes['columnsTablet'] ) ) : 2;
			$columns_desktop    = isset( $attributes['columnsDesktop'] ) ? max( 1, min( 4, (int) $attributes['columnsDesktop'] ) ) : $legacy_columns;
			$breakpoint_tablet  = isset( $attributes['breakpointTablet'] ) ? max( 320, (int) $attributes['breakpointTablet'] ) : 576;
			$breakpoint_desktop = isset( $attributes['breakpointDesktop'] ) ? max( 321, (int) $attributes['breakpointDesktop'] ) : 960;

			if ( $columns_tablet > 1 ) {
				$rules[] = array(
					'minWidth' => $breakpoint_tablet,
					'columns'  => $columns_tablet,
				);
			}

			if ( $columns_desktop > 1 ) {
				$rules[] = array(
					'minWidth' => max( $breakpoint_desktop, $breakpoint_tablet + 1 ),
					'columns'  => $columns_desktop,
				);
			}
		}

		usort(
			$rules,
			static function ( array $left, array $right ): int {
				return $left['minWidth'] <=> $right['minWidth'];
			}
		);

		$unique_rules = array();
		foreach ( $rules as $rule ) {
			$unique_rules[ (string) $rule['minWidth'] ] = $rule;
		}

		return array_values( $unique_rules );
	}

	/**
	 * Build pagination links for the current block.
	 *
	 * @param string $query_var Query var name.
	 * @param int    $current   Current page.
	 * @param int    $total     Total pages.
	 *
	 * @return array<int,string>
	 */
	private function build_pagination_links( string $query_var, int $current, int $total ): array {
		$links = paginate_links(
			array(
				'base'      => $this->build_page_url( $query_var, '%#%' ),
				'format'    => '',
				'current'   => $current,
				'total'     => $total,
				'type'      => 'array',
				'prev_text' => __( 'Назад', 'realtrigel-core' ),
				'next_text' => __( 'Вперед', 'realtrigel-core' ),
			)
		);

		return is_array( $links ) ? $links : array();
	}

	/**
	 * Build current page URL with block-specific page query var.
	 *
	 * @param string     $query_var Query var name.
	 * @param int|string $page      Page value.
	 *
	 * @return string
	 */
	private function build_page_url( string $query_var, $page ): string {
		$args = array();

		foreach ( $_GET as $key => $value ) {
			if ( $key === $query_var ) {
				continue;
			}

			if ( is_scalar( $value ) ) {
				$args[ sanitize_key( (string) $key ) ] = sanitize_text_field( wp_unslash( (string) $value ) );
				continue;
			}

			if ( is_array( $value ) ) {
				$args[ sanitize_key( (string) $key ) ] = array_values(
					array_filter(
						array_map(
							static function ( $item ): string {
								return is_scalar( $item ) ? sanitize_text_field( wp_unslash( (string) $item ) ) : '';
							},
							$value
						)
					)
				);
			}
		}

		if ( '1' !== (string) $page ) {
			$args[ $query_var ] = $page;
		}

		return str_replace( '%25%23%25', '%#%', add_query_arg( $args, $this->get_current_url() ) );
	}

	/**
	 * Get current catalog filters from request.
	 *
	 * @return array<string,string>
	 */
	public static function get_request_filters(): array {
		return array(
			'locations'     => self::get_request_values( self::FILTER_LOCATION ),
			'deal_type'     => self::get_request_value( self::FILTER_DEAL_TYPE ),
			'property_type' => self::get_request_value( self::FILTER_PROPERTY_TYPE ),
			'search'        => self::get_request_value( self::FILTER_SEARCH ),
			'price_min'     => self::get_request_value( self::FILTER_PRICE_MIN ),
			'price_max'     => self::get_request_value( self::FILTER_PRICE_MAX ),
			'area_min'      => self::get_request_value( self::FILTER_AREA_MIN ),
			'area_max'      => self::get_request_value( self::FILTER_AREA_MAX ),
			'sort'          => self::get_request_value( self::FILTER_SORT ),
		);
	}

	/**
	 * Apply shared request filters to query args.
	 *
	 * @param array<string,mixed> $query_args Base query args.
	 *
	 * @return array<string,mixed>
	 */
	private function apply_request_filters_to_query_args( array $query_args ): array {
		$filters    = self::get_request_filters();
		$tax_query  = array( 'relation' => 'AND' );
		$meta_query = array( 'relation' => 'AND' );

		if ( '' !== $filters['search'] ) {
			$query_args['s'] = $filters['search'];
		}

		if ( ! empty( $filters['locations'] ) ) {
			$tax_query[] = array(
				'taxonomy' => RR_Property_Location_Taxonomy::TAXONOMY,
				'field'    => 'slug',
				'terms'    => $filters['locations'],
			);
		}

		if ( '' !== $filters['deal_type'] ) {
			$tax_query[] = array(
				'taxonomy' => RR_Property_Deal_Type_Taxonomy::TAXONOMY,
				'field'    => 'slug',
				'terms'    => $filters['deal_type'],
			);
		}

		if ( '' !== $filters['property_type'] ) {
			$tax_query[] = array(
				'taxonomy' => RR_Property_Type_Taxonomy::TAXONOMY,
				'field'    => 'slug',
				'terms'    => $filters['property_type'],
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$query_args['tax_query'] = $tax_query;
		}

		if ( '' !== $filters['price_min'] && is_numeric( $filters['price_min'] ) ) {
			$meta_query[] = array(
				'key'     => 'price',
				'value'   => (float) $filters['price_min'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( '' !== $filters['price_max'] && is_numeric( $filters['price_max'] ) ) {
			$meta_query[] = array(
				'key'     => 'price',
				'value'   => (float) $filters['price_max'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		if ( '' !== $filters['area_min'] && is_numeric( $filters['area_min'] ) ) {
			$meta_query[] = array(
				'key'     => 'area',
				'value'   => (float) $filters['area_min'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( '' !== $filters['area_max'] && is_numeric( $filters['area_max'] ) ) {
			$meta_query[] = array(
				'key'     => 'area',
				'value'   => (float) $filters['area_max'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$query_args['meta_query'] = $meta_query;
		}

		switch ( $filters['sort'] ) {
			case 'price_asc':
				$query_args['meta_key'] = 'price';
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'ASC';
				break;
			case 'price_desc':
				$query_args['meta_key'] = 'price';
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				break;
			case 'title_asc':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'ASC';
				break;
			case 'title_desc':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'DESC';
				break;
			case 'date_desc':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		return $query_args;
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

	/**
	 * Render hidden inputs to preserve active filters in toolbar forms.
	 *
	 * @param array<string,mixed> $filters Current filters.
	 *
	 * @return void
	 */
	private function render_persisted_filters_inputs( array $filters ): void {
		foreach ( $filters as $filter_key => $filter_value ) {
			if ( 'sort' === $filter_key ) {
				continue;
			}

			$input_name = '';

			switch ( $filter_key ) {
				case 'locations':
					$input_name = self::FILTER_LOCATION . '[]';
					break;
				case 'deal_type':
					$input_name = self::FILTER_DEAL_TYPE;
					break;
				case 'property_type':
					$input_name = self::FILTER_PROPERTY_TYPE;
					break;
				case 'search':
					$input_name = self::FILTER_SEARCH;
					break;
				case 'price_min':
					$input_name = self::FILTER_PRICE_MIN;
					break;
				case 'price_max':
					$input_name = self::FILTER_PRICE_MAX;
					break;
				case 'area_min':
					$input_name = self::FILTER_AREA_MIN;
					break;
				case 'area_max':
					$input_name = self::FILTER_AREA_MAX;
					break;
			}

			if ( '' === $input_name ) {
				continue;
			}

			if ( is_array( $filter_value ) ) {
				foreach ( $filter_value as $item ) {
					if ( ! is_scalar( $item ) || '' === trim( (string) $item ) ) {
						continue;
					}

					printf(
						'<input type="hidden" name="%1$s" value="%2$s" />',
						esc_attr( $input_name ),
						esc_attr( (string) $item )
					);
				}

				continue;
			}

			if ( ! is_scalar( $filter_value ) || '' === trim( (string) $filter_value ) ) {
				continue;
			}

			printf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $input_name ),
				esc_attr( (string) $filter_value )
			);
		}
	}

	/**
	 * Get available sort options.
	 *
	 * @return array<string,string>
	 */
	private function get_sort_options(): array {
		return array(
			'date_desc'  => __( 'Сначала новые', 'realtrigel-core' ),
			'price_asc'  => __( 'Цена: по возрастанию', 'realtrigel-core' ),
			'price_desc' => __( 'Цена: по убыванию', 'realtrigel-core' ),
			'title_asc'  => __( 'Название: А-Я', 'realtrigel-core' ),
			'title_desc' => __( 'Название: Я-А', 'realtrigel-core' ),
		);
	}

	/**
	 * Get sanitized scalar request value.
	 *
	 * @param string $key Request key.
	 *
	 * @return string
	 */
	private static function get_request_value( string $key ): string {
		if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) );
	}

	/**
	 * Get sanitized request values list.
	 *
	 * @param string $key Request key.
	 *
	 * @return array<int,string>
	 */
	private static function get_request_values( string $key ): array {
		if ( ! isset( $_GET[ $key ] ) ) {
			return array();
		}

		$raw = $_GET[ $key ];

		if ( is_scalar( $raw ) ) {
			$value = sanitize_text_field( wp_unslash( (string) $raw ) );
			return '' !== $value ? array( $value ) : array();
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$values = array();

		foreach ( $raw as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( (string) $item ) );

			if ( '' !== $value ) {
				$values[] = $value;
			}
		}

		return array_values( array_unique( $values ) );
	}

	/**
	 * Build gallery image HTML list for a property.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<int,string>
	 */
	private function get_post_gallery_images( int $post_id ): array {
		$image_ids = array();

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id > 0 ) {
			$image_ids[] = (int) $thumbnail_id;
		}

		if ( function_exists( 'get_field' ) ) {
			$media_gallery = get_field( 'media_gallery', $post_id );

			if ( is_array( $media_gallery ) ) {
				foreach ( $media_gallery as $media_row ) {
					if ( ! is_array( $media_row ) || empty( $media_row['media'] ) || ! is_array( $media_row['media'] ) ) {
						continue;
					}

					$media_file    = $media_row['media'];
					$mime_type     = isset( $media_file['mime_type'] ) ? (string) $media_file['mime_type'] : '';
					$attachment_id = isset( $media_file['ID'] ) ? (int) $media_file['ID'] : 0;

					if ( $attachment_id > 0 && 0 === strpos( $mime_type, 'image/' ) ) {
						$image_ids[] = $attachment_id;
					}
				}
			}

			if ( empty( $image_ids ) ) {
				$gallery_items = get_field( 'gallery', $post_id );

				if ( is_array( $gallery_items ) ) {
					foreach ( $gallery_items as $gallery_item ) {
						$attachment_id = 0;

						if ( is_array( $gallery_item ) && isset( $gallery_item['ID'] ) ) {
							$attachment_id = (int) $gallery_item['ID'];
						} elseif ( is_numeric( $gallery_item ) ) {
							$attachment_id = (int) $gallery_item;
						}

						if ( $attachment_id > 0 ) {
							$image_ids[] = $attachment_id;
						}
					}
				}
			}
		}

		$attachment_ids = get_children(
			array(
				'post_parent'    => $post_id,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'fields'         => 'ids',
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
			)
		);

		if ( is_array( $attachment_ids ) ) {
			$image_ids = array_merge( $image_ids, array_map( 'intval', $attachment_ids ) );
		}

		$image_ids = array_values( array_unique( array_filter( $image_ids ) ) );

		if ( empty( $image_ids ) ) {
			return array();
		}

		$images = array();

		foreach ( $image_ids as $image_id ) {
			$image_html = wp_get_attachment_image( $image_id, 'large' );

			if ( '' !== trim( (string) $image_html ) ) {
				$images[] = $image_html;
			}
		}

		return $images;
	}
}

