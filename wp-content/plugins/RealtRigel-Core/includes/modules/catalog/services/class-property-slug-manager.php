<?php
/**
 * Property slug normalization service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Slug_Manager {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'wp_insert_post_data', array( $this, 'filter_post_data' ), 10, 2 );
	}

	/**
	 * Ensure latin URL slug for catalog posts.
	 *
	 * @param array<string,mixed> $data    Sanitized post data.
	 * @param array<string,mixed> $postarr Raw post data.
	 *
	 * @return array<string,mixed>
	 */
	public function filter_post_data( array $data, array $postarr ): array {
		if ( ! isset( $data['post_type'] ) || RR_Property_Post_Type::POST_TYPE !== $data['post_type'] ) {
			return $data;
		}

		$title = isset( $data['post_title'] ) ? (string) $data['post_title'] : '';
		$slug  = isset( $data['post_name'] ) ? (string) $data['post_name'] : '';

		if ( '' === $title ) {
			return $data;
		}

		$generated_slug = $this->generate_slug( $title );

		if ( '' !== $generated_slug && $this->slug_requires_regeneration( $slug ) ) {
			$data['post_name'] = $generated_slug;
		}

		return $data;
	}

	/**
	 * Generate latin slug from text.
	 *
	 * @param string $value Source value.
	 *
	 * @return string
	 */
	public function generate_slug( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );
		$value = remove_accents( $value );

		$map = array(
			'А' => 'A',  'а' => 'a',  'Б' => 'B',   'б' => 'b',   'В' => 'V',   'в' => 'v',
			'Г' => 'G',  'г' => 'g',  'Д' => 'D',   'д' => 'd',   'Е' => 'E',   'е' => 'e',
			'Ё' => 'Yo', 'ё' => 'yo', 'Ж' => 'Zh',  'ж' => 'zh',  'З' => 'Z',   'з' => 'z',
			'И' => 'I',  'и' => 'i',  'Й' => 'Y',   'й' => 'y',   'К' => 'K',   'к' => 'k',
			'Л' => 'L',  'л' => 'l',  'М' => 'M',   'м' => 'm',   'Н' => 'N',   'н' => 'n',
			'О' => 'O',  'о' => 'o',  'П' => 'P',   'п' => 'p',   'Р' => 'R',   'р' => 'r',
			'С' => 'S',  'с' => 's',  'Т' => 'T',   'т' => 't',   'У' => 'U',   'у' => 'u',
			'Ф' => 'F',  'ф' => 'f',  'Х' => 'Kh',  'х' => 'kh',  'Ц' => 'Ts',  'ц' => 'ts',
			'Ч' => 'Ch', 'ч' => 'ch', 'Ш' => 'Sh',  'ш' => 'sh',  'Щ' => 'Shch','щ' => 'shch',
			'Ъ' => '',   'ъ' => '',   'Ы' => 'Y',   'ы' => 'y',   'Ь' => '',    'ь' => '',
			'Э' => 'E',  'э' => 'e',  'Ю' => 'Yu',  'ю' => 'yu',  'Я' => 'Ya',  'я' => 'ya',
			'І' => 'I',  'і' => 'i',  'Ї' => 'Yi',  'ї' => 'yi',  'Є' => 'Ye',  'є' => 'ye',
			'Ґ' => 'G',  'ґ' => 'g',  'Ł' => 'L',   'ł' => 'l',   'Ó' => 'O',   'ó' => 'o',
			'Ą' => 'A',  'ą' => 'a',  'Ę' => 'E',   'ę' => 'e',   'Ć' => 'C',   'ć' => 'c',
			'Ń' => 'N',  'ń' => 'n',  'Ś' => 'S',   'ś' => 's',   'Ź' => 'Z',   'ź' => 'z',
			'Ż' => 'Z',  'ż' => 'z',
		);

		$value = strtr( $value, $map );
		$slug  = sanitize_title( strtolower( $value ) );

		return (string) $slug;
	}

	/**
	 * Determine if current slug must be replaced.
	 *
	 * @param string $slug Current slug.
	 *
	 * @return bool
	 */
	private function slug_requires_regeneration( string $slug ): bool {
		$slug = trim( $slug );

		if ( '' === $slug ) {
			return true;
		}

		if ( 1 === preg_match( '/[\p{Cyrillic}]/u', $slug ) ) {
			return true;
		}

		return 1 !== preg_match( '/^[a-z0-9-]+$/', $slug );
	}
}
