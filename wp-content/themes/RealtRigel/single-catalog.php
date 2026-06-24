<?php
/**
 * Single template for catalog objects.
 *
 * @package RealtRigel
 */

get_header();

$post_id             = get_the_ID();
$property_price      = function_exists( 'get_field' ) ? get_field( 'price', $post_id ) : '';
$property_area       = function_exists( 'get_field' ) ? get_field( 'area', $post_id ) : '';
$property_floor      = function_exists( 'get_field' ) ? get_field( 'floor', $post_id ) : '';
$property_currency   = function_exists( 'get_field' ) ? get_field( 'currency', $post_id ) : '';
$property_gallery    = function_exists( 'get_field' ) ? get_field( 'gallery', $post_id ) : array();
$property_media      = function_exists( 'get_field' ) ? get_field( 'media_gallery', $post_id ) : array();
$property_downloads  = function_exists( 'get_field' ) ? get_field( 'downloads', $post_id ) : array();
$property_parameters = function_exists( 'get_field' ) ? get_field( 'parameter_pairs', $post_id ) : array();
$post_content        = trim( (string) get_post_field( 'post_content', $post_id ) );
$map_payload         = function_exists( 'rr_get_catalog_map_payload' ) ? rr_get_catalog_map_payload( $post_id ) : null;

$property_currency    = is_string( $property_currency ) && '' !== $property_currency ? strtoupper( $property_currency ) : 'USD';
$property_price       = is_numeric( $property_price ) ? (float) $property_price : 0.0;
$property_area        = is_numeric( $property_area ) ? (float) $property_area : 0.0;
$property_floor       = is_numeric( $property_floor ) ? (int) $property_floor : 0;
$currency_options     = array( 'PLN', 'USD', 'EUR' );
$gallery_items        = array();
$download_items       = array();
$custom_rows          = array();
$summary_rows         = array();
$highlight_rows       = array();
$feature_terms        = get_the_terms( $post_id, 'property_feature' );
$property_type_terms  = get_the_terms( $post_id, 'property_type' );
$deal_type_terms      = get_the_terms( $post_id, 'property_deal_type' );
$location_terms       = get_the_terms( $post_id, 'property_location' );
$property_type_label  = '';
$deal_type_label      = '';
$deal_type_slug       = '';
$location_label       = '';
$download_all_url     = '';
$related_properties   = array();
$current_location_ids = array();
$current_type_ids     = array();
$current_deal_ids     = array();
$has_gallery_slider   = false;
$secondary_cta_target = '#rtg-about';
$secondary_cta_label  = __( 'Описание объекта', 'realtrigel' );
$price_period_label   = __( '/ объект', 'realtrigel' );
$price_per_meter      = 0.0;
$price_per_meter_text = '';
$formatted_price      = '';
$formatted_area       = '';
$formatted_floor      = '';
$catalog_page_url     = function_exists( 'rr_get_catalog_page_url' ) ? rr_get_catalog_page_url() : '';
$catalog_page_title   = __( 'Каталог', 'realtrigel' );
$show_catalog_crumb   = true;
$calculate_price_per_meter = function_exists( 'get_field' ) ? get_field( 'calculate_price_per_meter', $post_id ) : true;
$build_video_markup   = static function ( string $url, string $mime_type ): string {
	$source_url  = esc_url( $url );
	$source_type = esc_attr( $mime_type );

	return sprintf(
		'<video controls playsinline preload="metadata"><source src="%1$s"%2$s>%3$s</video>',
		$source_url,
		'' !== $mime_type ? ' type="' . $source_type . '"' : '',
		esc_html__( 'Ваш браузер не поддерживает видео.', 'realtrigel' )
	);
};

if ( ! in_array( $property_currency, $currency_options, true ) ) {
	$property_currency = 'USD';
}

if ( function_exists( 'rr_get_catalog_page_id' ) ) {
	$catalog_page_id = rr_get_catalog_page_id();

	if ( $catalog_page_id > 0 ) {
		$catalog_page_title = get_the_title( $catalog_page_id ) ?: $catalog_page_title;
	}
}

if ( '' === $catalog_page_url ) {
	$catalog_page_url = home_url( '/catalog/' );
}

$show_catalog_crumb = untrailingslashit( $catalog_page_url ) !== untrailingslashit( home_url( '/' ) );

$calculate_price_per_meter = false !== $calculate_price_per_meter;
$formatted_price = $property_price > 0 ? number_format_i18n( $property_price, 0 ) . ' ' . $property_currency : __( 'Договорная', 'realtrigel' );
$formatted_area  = $property_area > 0 ? sprintf(
	/* translators: %s: property area value. */
	__( '%s м²', 'realtrigel' ),
	number_format_i18n( $property_area, 1 )
) : '';
$formatted_floor = $property_floor > 0 ? number_format_i18n( $property_floor, 0 ) : '';
$summary_area_icon_url  = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'area', 'thumbnail' ) : '';
$summary_floor_icon_url = function_exists( 'realtrigel_get_theme_icon_url' ) ? realtrigel_get_theme_icon_url( 'floor', 'thumbnail' ) : '';

if ( is_array( $property_media ) ) {
	foreach ( $property_media as $media_row ) {
		if ( ! is_array( $media_row ) || empty( $media_row['media'] ) || ! is_array( $media_row['media'] ) ) {
			continue;
		}

		$media_file = $media_row['media'];
		$media_id   = isset( $media_file['ID'] ) ? (int) $media_file['ID'] : 0;
		$media_url  = isset( $media_file['url'] ) ? (string) $media_file['url'] : '';
		$mime_type  = isset( $media_file['mime_type'] ) ? (string) $media_file['mime_type'] : '';

		if ( '' === $media_url ) {
			continue;
		}

		$is_video = 0 === strpos( $mime_type, 'video/' );

		$gallery_items[] = array(
			'id'       => $media_id,
			'type'     => $is_video ? 'video' : 'image',
			'thumb'    => $is_video && $media_id > 0 ? wp_mime_type_icon( $media_id ) : ( $media_id > 0 ? (string) wp_get_attachment_image_url( $media_id, 'thumbnail' ) : $media_url ),
			'stage'    => $is_video
				? $build_video_markup( $media_url, $mime_type )
				: ( $media_id > 0 ? wp_get_attachment_image( $media_id, 'full', false, array( 'loading' => 'lazy' ) ) : '<img src="' . esc_url( $media_url ) . '" alt="">' ),
			'lightbox' => $is_video
				? $build_video_markup( $media_url, $mime_type )
				: ( $media_id > 0 ? wp_get_attachment_image( $media_id, 'full' ) : '<img src="' . esc_url( $media_url ) . '" alt="">' ),
		);
	}
}

if ( empty( $gallery_items ) && is_array( $property_gallery ) ) {
	foreach ( $property_gallery as $gallery_item ) {
		$attachment_id = 0;

		if ( is_array( $gallery_item ) && isset( $gallery_item['ID'] ) ) {
			$attachment_id = (int) $gallery_item['ID'];
		} elseif ( is_numeric( $gallery_item ) ) {
			$attachment_id = (int) $gallery_item;
		}

		if ( $attachment_id > 0 ) {
			$gallery_items[] = array(
				'id'       => $attachment_id,
				'type'     => 'image',
				'thumb'    => (string) wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
				'stage'    => wp_get_attachment_image( $attachment_id, 'full', false, array( 'loading' => 'lazy' ) ),
				'lightbox' => wp_get_attachment_image( $attachment_id, 'full' ),
			);
		}
	}
}

if ( empty( $gallery_items ) ) {
	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( $thumbnail_id > 0 ) {
		$gallery_items[] = array(
			'id'       => $thumbnail_id,
			'type'     => 'image',
			'thumb'    => (string) wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ),
			'stage'    => wp_get_attachment_image( $thumbnail_id, 'full', false, array( 'loading' => 'eager' ) ),
			'lightbox' => wp_get_attachment_image( $thumbnail_id, 'full' ),
		);
	}
}

$has_gallery_slider = count( $gallery_items ) > 1;

if ( is_array( $feature_terms ) ) {
	usort(
		$feature_terms,
		static function ( $left, $right ) {
			return strcmp( mb_strtolower( $left->name ), mb_strtolower( $right->name ) );
		}
	);
} else {
	$feature_terms = array();
}

if ( is_array( $property_downloads ) ) {
	foreach ( $property_downloads as $download_row ) {
		if ( ! is_array( $download_row ) || empty( $download_row['file'] ) || ! is_array( $download_row['file'] ) ) {
			continue;
		}

		$file_value = $download_row['file'];
		$file_url   = isset( $file_value['url'] ) ? (string) $file_value['url'] : '';

		if ( '' === $file_url ) {
			continue;
		}

		$file_title = isset( $download_row['title'] ) ? trim( (string) $download_row['title'] ) : '';
		$file_name  = isset( $file_value['filename'] ) ? (string) $file_value['filename'] : wp_basename( $file_url );

		$download_items[] = array(
			'title' => '' !== $file_title ? $file_title : $file_name,
			'url'   => $file_url,
		);
	}
}

if ( count( $download_items ) > 1 ) {
	$download_all_url = add_query_arg(
		array(
			'rr_catalog_download_all' => $post_id,
			'_wpnonce'                => wp_create_nonce( 'rr_catalog_download_all_' . $post_id ),
		),
		home_url( '/' )
	);
}

if ( is_array( $property_type_terms ) && ! empty( $property_type_terms ) ) {
	$property_type_label = $property_type_terms[0]->name;
	$current_type_ids    = wp_list_pluck( $property_type_terms, 'term_id' );
}

if ( is_array( $deal_type_terms ) && ! empty( $deal_type_terms ) ) {
	$deal_type_label  = $deal_type_terms[0]->name;
	$deal_type_slug   = $deal_type_terms[0]->slug;
	$current_deal_ids = wp_list_pluck( $deal_type_terms, 'term_id' );
}

if ( is_array( $location_terms ) && ! empty( $location_terms ) ) {
	$current_location_ids = wp_list_pluck( $location_terms, 'term_id' );
	$deepest_location     = null;
	$deepest_depth        = -1;

	foreach ( $location_terms as $location_term ) {
		$ancestors = get_ancestors( $location_term->term_id, 'property_location', 'taxonomy' );
		$depth     = is_array( $ancestors ) ? count( $ancestors ) : 0;

		if ( $depth > $deepest_depth ) {
			$deepest_depth    = $depth;
			$deepest_location = $location_term;
		}
	}

	if ( $deepest_location instanceof WP_Term ) {
		$location_names = array();
		$ancestor_ids   = get_ancestors( $deepest_location->term_id, 'property_location', 'taxonomy' );

		if ( is_array( $ancestor_ids ) ) {
			$ancestor_ids = array_reverse( $ancestor_ids );

			foreach ( $ancestor_ids as $ancestor_id ) {
				$ancestor_term = get_term( $ancestor_id, 'property_location' );

				if ( $ancestor_term instanceof WP_Term ) {
					$location_names[] = $ancestor_term->name;
				}
			}
		}

		$location_names[] = $deepest_location->name;
		$location_label   = implode( ', ', $location_names );
	}
}

if ( is_array( $property_parameters ) ) {
	foreach ( $property_parameters as $property_parameter ) {
		if ( ! is_array( $property_parameter ) ) {
			continue;
		}

		$parameter_label = isset( $property_parameter['label'] ) ? trim( (string) $property_parameter['label'] ) : '';
		$parameter_value = isset( $property_parameter['value'] ) ? trim( (string) $property_parameter['value'] ) : '';

		if ( '' === $parameter_label || '' === $parameter_value ) {
			continue;
		}

		$custom_rows[] = array(
			'label' => $parameter_label,
			'value' => $parameter_value,
		);
	}
}

if ( '' !== $formatted_area ) {
	$summary_rows[] = array(
		'type'  => 'area',
		'label' => __( 'Площадь', 'realtrigel' ),
		'value' => $formatted_area,
	);
}

if ( '' !== $formatted_floor ) {
	$summary_rows[] = array(
		'type'  => 'floor',
		'label' => __( 'Этаж', 'realtrigel' ),
		'value' => $formatted_floor,
	);
}

if ( '' !== $property_type_label ) {
	$summary_rows[] = array(
		'type'  => 'property_type',
		'label' => __( 'Тип объекта', 'realtrigel' ),
		'value' => $property_type_label,
	);
}

if ( '' !== $deal_type_label ) {
	$summary_rows[] = array(
		'type'  => 'deal_type',
		'label' => __( 'Тип сделки', 'realtrigel' ),
		'value' => $deal_type_label,
	);
}

if ( $calculate_price_per_meter && $property_price > 0 && $property_area > 0 ) {
	$price_per_meter      = $property_price / $property_area;
	$price_per_meter_text = sprintf(
		/* translators: 1: approximate price per square meter, 2: currency code. */
		__( '≈ %1$s %2$s / м²', 'realtrigel' ),
		number_format_i18n( $price_per_meter, 0 ),
		$property_currency
	);
}

if ( false !== stripos( $deal_type_slug, 'rent' ) || false !== stripos( $deal_type_slug, 'arenda' ) ) {
	$price_period_label = __( '/ месяц', 'realtrigel' );
}

if ( is_array( $map_payload ) ) {
	$secondary_cta_target = '#rtg-location';
	$secondary_cta_label  = __( 'Смотреть карту', 'realtrigel' );
} elseif ( ! empty( $download_items ) ) {
	$secondary_cta_target = '#rtg-files';
	$secondary_cta_label  = __( 'Файлы объекта', 'realtrigel' );
} elseif ( ! empty( $feature_terms ) ) {
	$secondary_cta_target = '#rtg-advantages';
	$secondary_cta_label  = __( 'Преимущества', 'realtrigel' );
}

$related_query = new WP_Query(
	array(
		'post_type'      => 'catalog',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post__not_in'   => array( $post_id ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( $related_query->have_posts() ) {
	while ( $related_query->have_posts() ) {
		$related_query->the_post();

		$related_id        = get_the_ID();
		$related_price_raw = function_exists( 'get_field' ) ? get_field( 'price', $related_id ) : '';
		$related_currency  = function_exists( 'get_field' ) ? get_field( 'currency', $related_id ) : '';
		$related_terms     = get_the_terms( $related_id, 'property_location' );
		$related_type_set  = get_the_terms( $related_id, 'property_type' );
		$related_deal_set  = get_the_terms( $related_id, 'property_deal_type' );
		$related_gallery   = function_exists( 'get_field' ) ? get_field( 'gallery', $related_id ) : array();
		$related_location  = '';
		$related_image     = get_the_post_thumbnail( $related_id, 'medium_large' );
		$related_score     = 0;

		$related_price_raw = is_numeric( $related_price_raw ) ? (float) $related_price_raw : 0.0;

		if ( is_array( $related_terms ) && ! empty( $related_terms ) ) {
			$related_deepest_location = null;
			$related_deepest_depth    = -1;

			foreach ( $related_terms as $related_location_term ) {
				$related_ancestors = get_ancestors( $related_location_term->term_id, 'property_location', 'taxonomy' );
				$related_depth     = is_array( $related_ancestors ) ? count( $related_ancestors ) : 0;

				if ( $related_depth > $related_deepest_depth ) {
					$related_deepest_depth    = $related_depth;
					$related_deepest_location = $related_location_term;
				}
			}

			if ( $related_deepest_location instanceof WP_Term ) {
				$related_location_names = array();
				$related_ancestor_ids   = get_ancestors( $related_deepest_location->term_id, 'property_location', 'taxonomy' );

				if ( is_array( $related_ancestor_ids ) ) {
					$related_ancestor_ids = array_reverse( $related_ancestor_ids );

					foreach ( $related_ancestor_ids as $related_ancestor_id ) {
						$related_ancestor_term = get_term( $related_ancestor_id, 'property_location' );

						if ( $related_ancestor_term instanceof WP_Term ) {
							$related_location_names[] = $related_ancestor_term->name;
						}
					}
				}

				$related_location_names[] = $related_deepest_location->name;
				$related_location         = implode( ', ', $related_location_names );
			}
		}

		$related_location_ids = is_array( $related_terms ) ? wp_list_pluck( $related_terms, 'term_id' ) : array();
		$related_type_ids     = is_array( $related_type_set ) ? wp_list_pluck( $related_type_set, 'term_id' ) : array();
		$related_deal_ids     = is_array( $related_deal_set ) ? wp_list_pluck( $related_deal_set, 'term_id' ) : array();

		if ( ! empty( $current_location_ids ) && ! empty( array_intersect( $current_location_ids, $related_location_ids ) ) ) {
			$related_score++;
		}

		if ( ! empty( $current_type_ids ) && ! empty( array_intersect( $current_type_ids, $related_type_ids ) ) ) {
			$related_score++;
		}

		if ( ! empty( $current_deal_ids ) && ! empty( array_intersect( $current_deal_ids, $related_deal_ids ) ) ) {
			$related_score++;
		}

		if ( $related_score < 2 ) {
			continue;
		}

		if ( '' === $related_image && is_array( $related_gallery ) && ! empty( $related_gallery ) ) {
			$related_gallery_item = reset( $related_gallery );
			$related_image_id     = 0;

			if ( is_array( $related_gallery_item ) && isset( $related_gallery_item['ID'] ) ) {
				$related_image_id = (int) $related_gallery_item['ID'];
			} elseif ( is_numeric( $related_gallery_item ) ) {
				$related_image_id = (int) $related_gallery_item;
			}

			if ( $related_image_id > 0 ) {
				$related_image = wp_get_attachment_image( $related_image_id, 'medium_large' );
			}
		}

		$related_properties[] = array(
			'id'          => $related_id,
			'title'       => get_the_title(),
			'url'         => get_permalink(),
			'location'    => $related_location,
			'price'       => $related_price_raw > 0 ? number_format_i18n( $related_price_raw, 0 ) . ' ' . strtoupper( (string) $related_currency ) : '',
			'image'       => $related_image,
			'match_score' => $related_score,
			'date'        => get_post_time( 'U', true, $related_id ),
		);
	}

	wp_reset_postdata();
}

if ( ! empty( $related_properties ) ) {
	usort(
		$related_properties,
		static function ( array $left, array $right ) {
			if ( (int) $left['match_score'] === (int) $right['match_score'] ) {
				return (int) $right['date'] <=> (int) $left['date'];
			}

			return (int) $right['match_score'] <=> (int) $left['match_score'];
		}
	);
}
?>
<div class="rtg-catalog-single container">
	<nav class="rtg-breadcrumbs" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'realtrigel' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Главная', 'realtrigel' ); ?></a>
		<?php if ( $show_catalog_crumb ) : ?>
			<span>/</span>
			<a href="<?php echo esc_url( $catalog_page_url ); ?>"><?php echo esc_html( $catalog_page_title ); ?></a>
			<span>/</span>
		<?php else : ?>
			<span>/</span>
		<?php endif; ?>
		<span><?php the_title(); ?></span>
	</nav>

	<article class="rtg-property-card">
		<section class="rtg-hero-layout">
			<div class="rtg-hero-main">
				<header class="rtg-hero-intro">
					<?php if ( '' !== $location_label ) : ?>
						<p class="rtg-address"><?php echo esc_html( $location_label ); ?></p>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<div class="rtg-property-tags">
						<?php if ( '' !== $property_type_label ) : ?>
							<span class="rtg-tag"><?php echo esc_html( $property_type_label ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $deal_type_label ) : ?>
							<span class="rtg-tag"><?php echo esc_html( $deal_type_label ); ?></span>
						<?php endif; ?>
					</div>
				</header>

				<section class="rtg-gallery-card">
					<div class="rtg-gallery" data-rtg-gallery>
						<div class="rtg-gallery-stage">
							<?php if ( ! empty( $gallery_items ) ) : ?>
								<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
									<div class="rtg-slide rtg-slide--<?php echo esc_attr( $gallery_item['type'] ); ?><?php echo 0 === $index ? ' is-active' : ''; ?>" data-slide-index="<?php echo esc_attr( (string) $index ); ?>">
										<?php echo $gallery_item['stage']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								<?php endforeach; ?>
								<button type="button" class="rtg-gallery-expand" data-gallery-open aria-label="<?php esc_attr_e( 'Открыть галерею в полноэкранном режиме', 'realtrigel' ); ?>">+</button>
								<div class="rtg-gallery-count">
									<?php
									printf(
										/* translators: %d: media items count. */
										esc_html__( '%d медиа', 'realtrigel' ),
										count( $gallery_items )
									);
									?>
								</div>
							<?php else : ?>
								<div class="rtg-slide is-active" data-slide-index="0"><?php esc_html_e( 'Фото отсутствуют', 'realtrigel' ); ?></div>
							<?php endif; ?>
						</div>

						<?php if ( $has_gallery_slider ) : ?>
							<div class="rtg-gallery-footer">
								<div class="rtg-gallery-nav">
									<button type="button" class="rtg-gallery-arrow" data-gallery-prev aria-label="<?php esc_attr_e( 'Предыдущее медиа', 'realtrigel' ); ?>">&larr;</button>
									<button type="button" class="rtg-gallery-arrow" data-gallery-next aria-label="<?php esc_attr_e( 'Следующее медиа', 'realtrigel' ); ?>">&rarr;</button>
								</div>
								<div class="rtg-gallery-dots rtg-gallery-dots--thumbs">
									<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
										<button type="button" class="<?php echo 0 === $index ? 'is-active' : ''; ?>" data-gallery-dot="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Медиа %d', 'realtrigel' ), $index + 1 ) ); ?>">
											<?php if ( 'video' === $gallery_item['type'] ) : ?>
												<span class="rtg-gallery-video-thumb">
													<?php if ( ! empty( $gallery_item['thumb'] ) ) : ?>
														<img src="<?php echo esc_url( $gallery_item['thumb'] ); ?>" alt="">
													<?php endif; ?>
													<span class="rtg-gallery-video-badge"><?php esc_html_e( 'Видео', 'realtrigel' ); ?></span>
												</span>
											<?php else : ?>
												<img src="<?php echo esc_url( $gallery_item['thumb'] ); ?>" alt="">
											<?php endif; ?>
										</button>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<aside class="rtg-summary-card">
				<div class="rtg-summary-card__top">
						<div class="rtg-summary-price-box" data-rtg-price-box data-base-price="<?php echo esc_attr( (string) $property_price ); ?>" data-base-currency="<?php echo esc_attr( $property_currency ); ?>" data-base-area="<?php echo esc_attr( (string) $property_area ); ?>" data-calculate-meter="<?php echo $calculate_price_per_meter ? '1' : '0'; ?>" data-nbp-api-base="<?php echo esc_url( 'https://api.nbp.pl/api' ); ?>">
							<p class="rtg-summary-price<?php echo $property_price <= 0 ? ' is-negotiable' : ''; ?>" data-rtg-price-value><?php echo wp_kses_post( preg_replace( '/\s+/u', '&nbsp;', $formatted_price ) ); ?></p>
							<?php if ( $property_price > 0 ) : ?>
								<p class="rtg-summary-period"><?php echo esc_html( $price_period_label ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $price_per_meter_text ) : ?>
								<p class="rtg-summary-meter" data-rtg-price-meter><?php echo esc_html( $price_per_meter_text ); ?></p>
							<?php endif; ?>
						</div>

					<div class="rtg-summary-actions">
					<button type="button" class="rtg-button rtg-button--primary" data-rtg-contact-open><?php esc_html_e( 'Получить консультацию', 'realtrigel' ); ?></button>
						<a class="rtg-button rtg-button--secondary" href="<?php echo esc_url( $secondary_cta_target ); ?>"><?php echo esc_html( $secondary_cta_label ); ?></a>
					</div>
				</div>

				<?php if ( ! empty( $summary_rows ) ) : ?>
					<ul class="rtg-summary-specs">
						<?php foreach ( $summary_rows as $summary_row ) : ?>
							<?php
							if (
								( '' !== $property_type_label && isset( $summary_row['value'] ) && $summary_row['value'] === $property_type_label ) ||
								( '' !== $deal_type_label && isset( $summary_row['value'] ) && $summary_row['value'] === $deal_type_label )
							) {
								continue;
							}

							$summary_icon_url = '';

							if ( isset( $summary_row['type'] ) && 'area' === $summary_row['type'] ) {
								$summary_icon_url = $summary_area_icon_url;
							} elseif ( isset( $summary_row['type'] ) && 'floor' === $summary_row['type'] ) {
								$summary_icon_url = $summary_floor_icon_url;
							}
							?>
							<li class="rtg-summary-spec">
								<span class="rtg-summary-icon" aria-hidden="true">
									<?php if ( '' !== $summary_icon_url ) : ?>
										<img src="<?php echo esc_url( $summary_icon_url ); ?>" alt="">
									<?php endif; ?>
								</span>
								<div class="rtg-summary-copy">
									<span class="rtg-summary-label"><?php echo esc_html( $summary_row['label'] ); ?></span>
									<strong class="rtg-summary-value"><?php echo esc_html( $summary_row['value'] ); ?></strong>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</aside>
		</section>

		<div class="rtg-content-stack">
			<section class="rtg-section-card rtg-section-card--about" id="rtg-about">
				<div class="rtg-section-heading">
					<p class="rtg-section-kicker"><?php esc_html_e( 'О помещении', 'realtrigel' ); ?></p>
					<h2><?php esc_html_e( 'Пространство, готовое к работе', 'realtrigel' ); ?></h2>
				</div>

				<div class="rtg-rich-text">
					<?php if ( '' !== $post_content ) : ?>
						<?php echo apply_filters( 'the_content', $post_content ); ?>
					<?php else : ?>
						<p><?php esc_html_e( 'Описание объекта пока не заполнено.', 'realtrigel' ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $summary_rows ) ) : ?>
					<div class="rtg-about-highlights">
						<?php foreach ( array_slice( $summary_rows, 0, 4 ) as $summary_row ) : ?>
							<div class="rtg-highlight-card">
								<span class="rtg-highlight-label"><?php echo esc_html( $summary_row['label'] ); ?></span>
								<strong class="rtg-highlight-value"><?php echo esc_html( $summary_row['value'] ); ?></strong>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $feature_terms ) ) : ?>
				<section class="rtg-section-card" id="rtg-advantages">
					<div class="rtg-section-heading">
						<p class="rtg-section-kicker"><?php esc_html_e( 'Преимущества', 'realtrigel' ); ?></p>
						<h2><?php esc_html_e( 'Ключевые особенности объекта', 'realtrigel' ); ?></h2>
					</div>

					<div class="rtg-benefits-grid">
						<?php foreach ( $feature_terms as $feature_term ) : ?>
							<div class="rtg-benefit-item">
								<span class="rtg-benefit-icon" aria-hidden="true"></span>
								<span><?php echo esc_html( $feature_term->name ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $custom_rows ) ) : ?>
				<section class="rtg-section-card" id="rtg-technical">
					<div class="rtg-section-heading">
						<p class="rtg-section-kicker"><?php esc_html_e( 'Технические особенности', 'realtrigel' ); ?></p>
						<h2><?php esc_html_e( 'Практические параметры помещения', 'realtrigel' ); ?></h2>
					</div>

					<div class="rtg-tech-grid">
						<?php foreach ( $custom_rows as $custom_row ) : ?>
							<div class="rtg-tech-item">
								<span class="rtg-tech-icon" aria-hidden="true"></span>
								<div class="rtg-tech-copy">
									<span class="rtg-tech-label"><?php echo esc_html( $custom_row['label'] ); ?></span>
									<strong class="rtg-tech-value"><?php echo esc_html( $custom_row['value'] ); ?></strong>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $download_items ) ) : ?>
				<section class="rtg-section-card" id="rtg-files">
					<div class="rtg-section-heading">
						<p class="rtg-section-kicker"><?php esc_html_e( 'Материалы объекта', 'realtrigel' ); ?></p>
						<h2><?php esc_html_e( 'Файлы и дополнительные документы', 'realtrigel' ); ?></h2>
					</div>

					<ul class="rtg-download-list">
						<?php foreach ( $download_items as $download_item ) : ?>
							<li class="rtg-download-item">
								<div class="rtg-download-copy">
									<a class="rtg-download-link" href="<?php echo esc_url( $download_item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $download_item['title'] ); ?></a>
								</div>
								<a class="rtg-download-action" href="<?php echo esc_url( $download_item['url'] ); ?>" download><?php esc_html_e( 'Скачать', 'realtrigel' ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ( '' !== $download_all_url ) : ?>
						<a class="rtg-button rtg-button--secondary rtg-button--files" href="<?php echo esc_url( $download_all_url ); ?>"><?php esc_html_e( 'Скачать все', 'realtrigel' ); ?></a>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( is_array( $map_payload ) ) : ?>
				<section class="rtg-section-card rtg-map-block" id="rtg-location">
					<div class="rtg-section-heading rtg-map-block__header">
						<p class="rtg-section-kicker"><?php esc_html_e( 'Расположение', 'realtrigel' ); ?></p>
						<h2><?php esc_html_e( 'Локация объекта', 'realtrigel' ); ?></h2>
						<?php if ( ! empty( $map_payload['exact'] ) && ! empty( $map_payload['address'] ) ) : ?>
							<p class="rtg-map-block__address"><?php echo esc_html( (string) $map_payload['address'] ); ?></p>
						<?php endif; ?>
					</div>
					<div class="rtg-map-canvas" data-rtg-map></div>
					<script type="application/json" data-rtg-map-data><?php echo wp_json_encode( $map_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
				</section>
				<div class="rtg-section-cta rtg-section-cta--center">
					<button type="button" class="rtg-button rtg-button--primary rtg-button--inline" data-rtg-contact-open><?php esc_html_e( 'Получить консультацию', 'realtrigel' ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $related_properties ) ) : ?>
				<section class="rtg-related has-slider" data-rtg-related-slider>
					<div class="rtg-section-heading rtg-section-heading--row">
						<div>
							<p class="rtg-section-kicker"><?php esc_html_e( 'Похожие объекты', 'realtrigel' ); ?></p>
							<h2><?php esc_html_e( 'Похожие предложения в каталоге', 'realtrigel' ); ?></h2>
						</div>
						<div class="rtg-related-slider-controls">
							<button type="button" class="rtg-related-slider-arrow rtg-related-slider-arrow--prev" data-rtg-related-prev aria-label="<?php esc_attr_e( 'Предыдущие объекты', 'realtrigel' ); ?>">&larr;</button>
							<button type="button" class="rtg-related-slider-arrow rtg-related-slider-arrow--next" data-rtg-related-next aria-label="<?php esc_attr_e( 'Следующие объекты', 'realtrigel' ); ?>">&rarr;</button>
						</div>
					</div>

					<div class="rtg-related-grid-wrapper">
						<div class="rtg-related-grid" data-rtg-related-track>
							<?php foreach ( $related_properties as $related_property ) : ?>
								<a class="rtg-related-card" href="<?php echo esc_url( $related_property['url'] ); ?>">
									<div class="rtg-related-image<?php echo '' === $related_property['image'] ? ' rtg-related-image--empty' : ''; ?>">
										<?php if ( '' !== $related_property['image'] ) : ?>
											<?php echo $related_property['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php else : ?>
											<span><?php esc_html_e( 'Фото', 'realtrigel' ); ?></span>
										<?php endif; ?>
										<span class="rtg-related-image-overlay"></span>
										<?php if ( '' !== $related_property['price'] ) : ?>
											<span class="rtg-related-price"><?php echo esc_html( $related_property['price'] ); ?></span>
										<?php endif; ?>
									</div>
									<div class="rtg-related-meta">
										<?php if ( '' !== $related_property['location'] ) : ?>
											<span class="rtg-related-location"><?php echo esc_html( $related_property['location'] ); ?></span>
										<?php endif; ?>
										<strong><?php echo esc_html( $related_property['title'] ); ?></strong>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</article>
</div>

<div class="rtg-contact-modal" data-rtg-contact-modal hidden>
	<div class="rtg-contact-backdrop" data-rtg-contact-close></div>
	<div class="rtg-contact-dialog" role="dialog" aria-modal="true" aria-labelledby="rtg-contact-title">
		<div class="rtg-contact-header">
			<div>
				<h3 id="rtg-contact-title"><?php esc_html_e( 'Получить консультацию', 'realtrigel' ); ?></h3>
				<p><?php esc_html_e( 'Оставьте имя и хотя бы один способ связи. Мы свяжемся с вами по этому объекту.', 'realtrigel' ); ?></p>
			</div>
			<button type="button" class="rtg-contact-close" data-rtg-contact-close aria-label="<?php esc_attr_e( 'Закрыть', 'realtrigel' ); ?>">&times;</button>
		</div>

		<form class="rtg-contact-form" data-rtg-contact-form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="realtrigel_property_contact">
			<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) get_the_ID() ); ?>">
			<?php wp_nonce_field( 'rtg_property_contact', 'rtg_property_contact_nonce' ); ?>

			<div class="rtg-contact-grid">
				<div class="rtg-contact-field">
					<label for="rtg-contact-name"><?php esc_html_e( 'Имя', 'realtrigel' ); ?></label>
					<input id="rtg-contact-name" type="text" name="contact_name" required>
				</div>

				<div class="rtg-contact-field">
					<label for="rtg-contact-phone"><?php esc_html_e( 'Телефон', 'realtrigel' ); ?></label>
					<input id="rtg-contact-phone" type="text" name="contact_phone">
				</div>

				<div class="rtg-contact-field">
					<label for="rtg-contact-email"><?php esc_html_e( 'Email', 'realtrigel' ); ?></label>
					<input id="rtg-contact-email" type="email" name="contact_email">
				</div>

				<div class="rtg-contact-field">
					<label for="rtg-contact-telegram"><?php esc_html_e( 'Telegram', 'realtrigel' ); ?></label>
					<input id="rtg-contact-telegram" type="text" name="contact_telegram">
				</div>

				<div class="rtg-contact-field">
					<label for="rtg-contact-partner-code"><?php esc_html_e( 'Partner code', 'realtrigel' ); ?></label>
					<input id="rtg-contact-partner-code" type="text" name="partner_code">
				</div>

				<div class="rtg-contact-field rtg-contact-field--wide">
					<label for="rtg-contact-message"><?php esc_html_e( 'Message', 'realtrigel' ); ?></label>
					<textarea id="rtg-contact-message" name="contact_message" rows="4"></textarea>
				</div>
			</div>

			<p class="rtg-contact-note"><?php esc_html_e( 'Имя обязательно. Нужно заполнить хотя бы одно из полей: телефон, email или Telegram.', 'realtrigel' ); ?></p>
			<p class="rtg-contact-error" data-rtg-contact-error hidden></p>
			<p class="rtg-contact-success" data-rtg-contact-success hidden><?php esc_html_e( 'Спасибо. Заявка отправлена.', 'realtrigel' ); ?></p>

			<button type="submit" class="rtg-button rtg-button--primary rtg-contact-submit"><?php esc_html_e( 'Отправить', 'realtrigel' ); ?></button>
		</form>
	</div>
</div>

<div class="rtg-lightbox" data-rtg-lightbox hidden>
	<div class="rtg-lightbox-backdrop" data-lightbox-close></div>
	<div class="rtg-lightbox-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Просмотр галереи', 'realtrigel' ); ?>">
		<button type="button" class="rtg-lightbox-close" data-lightbox-close aria-label="<?php esc_attr_e( 'Закрыть', 'realtrigel' ); ?>">&times;</button>

		<div class="rtg-lightbox-stage">
			<?php if ( ! empty( $gallery_items ) ) : ?>
				<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
					<div class="rtg-lightbox-slide rtg-lightbox-slide--<?php echo esc_attr( $gallery_item['type'] ); ?><?php echo 0 === $index ? ' is-active' : ''; ?>" data-lightbox-slide="<?php echo esc_attr( (string) $index ); ?>">
						<?php echo $gallery_item['lightbox']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="rtg-lightbox-slide is-active" data-lightbox-slide="0"><?php esc_html_e( 'Фото отсутствуют', 'realtrigel' ); ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $has_gallery_slider ) : ?>
			<button type="button" class="rtg-lightbox-arrow rtg-lightbox-arrow--prev" data-lightbox-prev aria-label="<?php esc_attr_e( 'Предыдущее медиа', 'realtrigel' ); ?>">&larr;</button>
			<button type="button" class="rtg-lightbox-arrow rtg-lightbox-arrow--next" data-lightbox-next aria-label="<?php esc_attr_e( 'Следующее медиа', 'realtrigel' ); ?>">&rarr;</button>

			<div class="rtg-lightbox-dots">
				<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
					<button type="button" class="<?php echo 0 === $index ? 'is-active' : ''; ?>" data-lightbox-dot="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Медиа %d', 'realtrigel' ), $index + 1 ) ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();

