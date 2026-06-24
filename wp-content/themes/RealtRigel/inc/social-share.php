<?php
/**
 * Social share helpers for catalog objects.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize plain text for social sharing.
 *
 * @param string $text Raw text.
 *
 * @return string
 */
function realtrigel_normalize_share_text( string $text ): string {
	$text = (string) preg_replace( '/<\s*br\s*\/?>/iu', "\n", $text );
	$text = (string) preg_replace( '/<\s*\/p\s*>/iu', "\n\n", $text );
	$text = wp_strip_all_tags( strip_shortcodes( $text ) );
	$text = (string) preg_replace( "/\r\n?/", "\n", $text );
	$text = (string) preg_replace( "/[ \t]+\n/u", "\n", $text );
	$text = (string) preg_replace( "/\n{3,}/u", "\n\n", $text );
	$text = (string) preg_replace( '/[ \t]{2,}/u', ' ', $text );

	return trim( (string) $text );
}

/**
 * Resolve social description for a catalog object.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function realtrigel_get_catalog_social_description( int $post_id ): string {
	$social_description = function_exists( 'get_field' ) ? get_field( 'social_description', $post_id ) : '';

	if ( is_string( $social_description ) && '' !== trim( $social_description ) ) {
		return realtrigel_normalize_share_text( $social_description );
	}

	$excerpt = get_the_excerpt( $post_id );
	if ( is_string( $excerpt ) && '' !== trim( $excerpt ) ) {
		return realtrigel_normalize_share_text( $excerpt );
	}

	$content = get_post_field( 'post_content', $post_id );
	if ( is_string( $content ) && '' !== trim( $content ) ) {
		return wp_trim_words( realtrigel_normalize_share_text( $content ), 36 );
	}

	return '';
}

/**
 * Resolve share image for a catalog object.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function realtrigel_get_catalog_social_image( int $post_id ): string {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( $thumbnail_id > 0 ) {
		$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );

		if ( is_string( $image_url ) && '' !== $image_url ) {
			return $image_url;
		}
	}

	$media_gallery = function_exists( 'get_field' ) ? get_field( 'media_gallery', $post_id ) : array();
	if ( is_array( $media_gallery ) ) {
		foreach ( $media_gallery as $media_row ) {
			if ( ! is_array( $media_row ) || empty( $media_row['media'] ) || ! is_array( $media_row['media'] ) ) {
				continue;
			}

			$media_file = $media_row['media'];
			$mime_type  = isset( $media_file['mime_type'] ) ? (string) $media_file['mime_type'] : '';
			$media_id   = isset( $media_file['ID'] ) ? (int) $media_file['ID'] : 0;

			if ( 0 === strpos( $mime_type, 'image/' ) ) {
				if ( $media_id > 0 ) {
					$image_url = wp_get_attachment_image_url( $media_id, 'full' );

					if ( is_string( $image_url ) && '' !== $image_url ) {
						return $image_url;
					}
				}

				if ( ! empty( $media_file['url'] ) && is_string( $media_file['url'] ) ) {
					return $media_file['url'];
				}
			}
		}
	}

	$gallery = function_exists( 'get_field' ) ? get_field( 'gallery', $post_id ) : array();
	if ( is_array( $gallery ) ) {
		foreach ( $gallery as $gallery_item ) {
			$attachment_id = 0;

			if ( is_array( $gallery_item ) && isset( $gallery_item['ID'] ) ) {
				$attachment_id = (int) $gallery_item['ID'];
			} elseif ( is_numeric( $gallery_item ) ) {
				$attachment_id = (int) $gallery_item;
			}

			if ( $attachment_id > 0 ) {
				$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

				if ( is_string( $image_url ) && '' !== $image_url ) {
					return $image_url;
				}
			}
		}
	}

	return '';
}

/**
 * Build social share payload for catalog object.
 *
 * @param int $post_id Post ID.
 *
 * @return array<string,string>
 */
function realtrigel_get_catalog_share_payload( int $post_id ): array {
	$title       = get_the_title( $post_id );
	$url         = get_permalink( $post_id );
	$description = realtrigel_get_catalog_social_description( $post_id );
	$image       = realtrigel_get_catalog_social_image( $post_id );
	$share_text  = trim( $description . "\n\n" . $url );

	return array(
		'title'           => is_string( $title ) ? $title : '',
		'description'     => $description,
		'share_text'      => $share_text,
		'image'           => $image,
		'url'             => is_string( $url ) ? $url : '',
		'labels'          => array(
			'negotiable'       => __( 'Договорная', 'realtrigel' ),
			'meterUnit'        => __( 'м²', 'realtrigel' ),
			'shareButton'      => __( 'Поделиться', 'realtrigel' ),
			'shareTitle'       => __( 'Поделиться объектом', 'realtrigel' ),
			'shareDescription' => __( 'Выберите способ отправки или скопируйте ссылку на этот объект.', 'realtrigel' ),
			'close'            => __( 'Закрыть', 'realtrigel' ),
			'copyLink'         => __( 'Скопировать ссылку', 'realtrigel' ),
			'copyFailed'       => __( 'Не удалось скопировать автоматически. Скопируйте текст вручную.', 'realtrigel' ),
			'linkCopied'       => __( 'Ссылка скопирована.', 'realtrigel' ),
			'instagramCopied'  => __( 'Текст скопирован. Откройте Instagram и вставьте его в публикацию.', 'realtrigel' ),
			'contactRequired'  => __( 'Укажите имя и хотя бы один способ связи: телефон, email или Telegram.', 'realtrigel' ),
			'contactError'     => __( 'Форма не отправлена. Укажите имя и хотя бы один способ связи.', 'realtrigel' ),
		),
	);
}

/**
 * Output social meta tags for catalog object pages.
 *
 * @return void
 */
function realtrigel_output_catalog_social_meta(): void {
	if ( ! is_singular( 'catalog' ) ) {
		return;
	}

	$post_id  = get_queried_object_id();
	$payload  = realtrigel_get_catalog_share_payload( $post_id );
	$title    = $payload['title'];
	$desc     = $payload['description'];
	$image    = $payload['image'];
	$url      = $payload['url'];

	if ( '' === $title || '' === $url ) {
		return;
	}

	echo "\n" . '<meta property="og:type" content="article" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";

	if ( '' !== $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
	}

	echo '<meta name="twitter:card" content="' . esc_attr( '' !== $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";

	if ( '' !== $image ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'realtrigel_output_catalog_social_meta', 5 );
