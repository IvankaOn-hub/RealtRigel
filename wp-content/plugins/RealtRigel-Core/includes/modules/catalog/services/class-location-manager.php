<?php
/**
 * Location chain finder/creator service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Location_Manager {

	/**
	 * Ordered levels map.
	 *
	 * @var string[]
	 */
	private array $levels = array( 'country', 'region', 'city', 'district' );

	/**
	 * Get existing or create missing location terms by levels.
	 *
	 * @param array<string,string> $data Raw location values.
	 *
	 * @return array<string,int>|\WP_Error
	 */
	public function get_or_create_location_terms( array $data ) {
		$location_data = $this->sanitize_location_data( $data );
		$parent_id     = 0;
		$terms_by_level = array(
			'country'  => 0,
			'region'   => 0,
			'city'     => 0,
			'district' => 0,
		);

		foreach ( $this->levels as $level ) {
			$name = $location_data[ $level ];

			if ( '' === $name ) {
				continue;
			}

			$term_id = $this->find_term_by_name_and_parent( $name, $parent_id );

			if ( is_wp_error( $term_id ) ) {
				return $term_id;
			}

			if ( 0 === $term_id ) {
				$term_id = $this->create_term( $name, $parent_id, $level );

				if ( is_wp_error( $term_id ) ) {
					return $term_id;
				}
			} else {
				update_term_meta( $term_id, 'location_level', $level );
			}

			$terms_by_level[ $level ] = $term_id;
			$parent_id                = $term_id;
		}

		if ( 0 === max( $terms_by_level ) ) {
			return new \WP_Error(
				'rr_location_chain_empty',
				__( 'Нужно указать хотя бы один уровень локации.', 'realtrigel-core' )
			);
		}

		return $terms_by_level;
	}

	/**
	 * Get existing or create missing location chain.
	 *
	 * @param array<string,string> $data Raw location values.
	 *
	 * @return int|\WP_Error
	 */
	public function get_or_create_location_chain( array $data ) {
		$terms_by_level = $this->get_or_create_location_terms( $data );

		if ( is_wp_error( $terms_by_level ) ) {
			return $terms_by_level;
		}

		$ordered_ids = array_values(
			array_filter(
				$terms_by_level,
				static fn( int $term_id ): bool => $term_id > 0
			)
		);

		$deepest_id = end( $ordered_ids );

		return false === $deepest_id ? 0 : (int) $deepest_id;
	}

	/**
	 * Sanitize location data.
	 *
	 * @param array<string,string> $data Raw data.
	 *
	 * @return array<string,string>
	 */
	private function sanitize_location_data( array $data ): array {
		$clean = array();

		foreach ( $this->levels as $level ) {
			$value          = isset( $data[ $level ] ) ? (string) $data[ $level ] : '';
			$clean[ $level ] = $this->normalize_value( $value );
		}

		return $clean;
	}

	/**
	 * Normalize incoming value.
	 *
	 * @param string $value Input value.
	 *
	 * @return string
	 */
	private function normalize_value( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );

		return $value;
	}

	/**
	 * Find term by exact name and parent.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID.
	 *
	 * @return int|\WP_Error
	 */
	private function find_term_by_name_and_parent( string $name, int $parent_id ) {
		$terms = get_terms(
			array(
				'taxonomy'   => RR_Property_Location_Taxonomy::TAXONOMY,
				'hide_empty' => false,
				'parent'     => $parent_id,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		foreach ( $terms as $term ) {
			if ( 0 === strcasecmp( $term->name, $name ) ) {
				return (int) $term->term_id;
			}
		}

		return 0;
	}

	/**
	 * Create location term.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID.
	 * @param string $level     Location level.
	 *
	 * @return int|\WP_Error
	 */
	private function create_term( string $name, int $parent_id, string $level ) {
		$result = wp_insert_term(
			$name,
			RR_Property_Location_Taxonomy::TAXONOMY,
			array(
				'slug'   => $this->generate_slug( $name ),
				'parent' => $parent_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! isset( $result['term_id'] ) ) {
			return new \WP_Error(
				'rr_insert_term_invalid_response',
				__( 'Не удалось создать термин из-за некорректного ответа.', 'realtrigel-core' )
			);
		}

		$term_id = (int) $result['term_id'];
		update_term_meta( $term_id, 'location_level', $level );

		return $term_id;
	}

	/**
	 * Generate latin slug for location value.
	 *
	 * @param string $value Source term name.
	 *
	 * @return string
	 */
	private function generate_slug( string $value ): string {
		$value = $this->normalize_value( $value );
		$value = remove_accents( $value );

		$map = array(
			'А' => 'A', 'а' => 'a', 'Б' => 'B', 'б' => 'b', 'В' => 'V', 'в' => 'v',
			'Г' => 'G', 'г' => 'g', 'Д' => 'D', 'д' => 'd', 'Е' => 'E', 'е' => 'e',
			'Ё' => 'Yo', 'ё' => 'yo', 'Ж' => 'Zh', 'ж' => 'zh', 'З' => 'Z', 'з' => 'z',
			'И' => 'I', 'и' => 'i', 'Й' => 'Y', 'й' => 'y', 'К' => 'K', 'к' => 'k',
			'Л' => 'L', 'л' => 'l', 'М' => 'M', 'м' => 'm', 'Н' => 'N', 'н' => 'n',
			'О' => 'O', 'о' => 'o', 'П' => 'P', 'п' => 'p', 'Р' => 'R', 'р' => 'r',
			'С' => 'S', 'с' => 's', 'Т' => 'T', 'т' => 't', 'У' => 'U', 'у' => 'u',
			'Ф' => 'F', 'ф' => 'f', 'Х' => 'Kh', 'х' => 'kh', 'Ц' => 'Ts', 'ц' => 'ts',
			'Ч' => 'Ch', 'ч' => 'ch', 'Ш' => 'Sh', 'ш' => 'sh', 'Щ' => 'Shch', 'щ' => 'shch',
			'Ъ' => '', 'ъ' => '', 'Ы' => 'Y', 'ы' => 'y', 'Ь' => '', 'ь' => '',
			'Э' => 'E', 'э' => 'e', 'Ю' => 'Yu', 'ю' => 'yu', 'Я' => 'Ya', 'я' => 'ya',
			'І' => 'I', 'і' => 'i', 'Ї' => 'Yi', 'ї' => 'yi', 'Є' => 'Ye', 'є' => 'ye',
			'Ґ' => 'G', 'ґ' => 'g', 'Ł' => 'L', 'ł' => 'l', 'Ó' => 'O', 'ó' => 'o',
			'Ą' => 'A', 'ą' => 'a', 'Ę' => 'E', 'ę' => 'e', 'Ć' => 'C', 'ć' => 'c',
			'Ń' => 'N', 'ń' => 'n', 'Ś' => 'S', 'ś' => 's', 'Ź' => 'Z', 'ź' => 'z',
			'Ż' => 'Z', 'ż' => 'z',
		);

		$value = strtr( $value, $map );
		$slug  = sanitize_title( $value );

		if ( '' === $slug ) {
			return 'location';
		}

		return $slug;
	}
}
