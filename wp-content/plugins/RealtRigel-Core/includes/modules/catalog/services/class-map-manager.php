<?php
/**
 * Catalog map manager.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Map_Manager {

	/**
	 * Meta key: map address.
	 *
	 * @var string
	 */
	public const FIELD_ADDRESS = 'map_address';

	/**
	 * Meta key: latitude.
	 *
	 * @var string
	 */
	public const FIELD_LAT = 'map_lat';

	/**
	 * Meta key: longitude.
	 *
	 * @var string
	 */
	public const FIELD_LNG = 'map_lng';

	/**
	 * Meta key: exact location visibility flag.
	 *
	 * @var string
	 */
	public const FIELD_SHOW_EXACT = 'map_show_exact_location';

	/**
	 * Nominatim endpoint.
	 *
	 * @var string
	 */
	private const NOMINATIM_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'acf/save_post', array( $this, 'maybe_geocode_from_acf' ), 17 );
	}

	/**
	 * Geocode address after ACF saves field values.
	 *
	 * @param int|string $post_id Post ID from ACF.
	 * @return void
	 */
	public function maybe_geocode_from_acf( $post_id ): void {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		$post_id = (int) $post_id;

		if ( $post_id <= 0 || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || RR_Property_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		$current_lat = get_post_meta( $post_id, self::FIELD_LAT, true );
		$current_lng = get_post_meta( $post_id, self::FIELD_LNG, true );

		if ( self::has_valid_coordinates( $current_lat, $current_lng ) ) {
			return;
		}

		$address = trim( (string) get_post_meta( $post_id, self::FIELD_ADDRESS, true ) );

		if ( '' === $address ) {
			return;
		}

		$coordinates = $this->geocode_address( $address );

		if ( null === $coordinates ) {
			return;
		}

		update_post_meta( $post_id, self::FIELD_LAT, $coordinates['lat'] );
		update_post_meta( $post_id, self::FIELD_LNG, $coordinates['lng'] );
	}

	/**
	 * Build safe map payload for frontend.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	public static function build_map_payload( int $post_id ): ?array {
		$lat = get_post_meta( $post_id, self::FIELD_LAT, true );
		$lng = get_post_meta( $post_id, self::FIELD_LNG, true );

		if ( ! self::has_valid_coordinates( $lat, $lng ) ) {
			return null;
		}

		$latitude   = (float) $lat;
		$longitude  = (float) $lng;
		$address    = trim( (string) get_post_meta( $post_id, self::FIELD_ADDRESS, true ) );
		$show_exact = self::is_truthy( get_post_meta( $post_id, self::FIELD_SHOW_EXACT, true ) );

		if ( $show_exact ) {
			return array(
				'lat'     => $latitude,
				'lng'     => $longitude,
				'zoom'    => 17,
				'exact'   => true,
				'marker'  => true,
				'address' => $address,
			);
		}

		$blurred = self::blur_coordinates( $latitude, $longitude, $post_id );

		return array(
			'lat'     => $blurred['lat'],
			'lng'     => $blurred['lng'],
			'zoom'    => 17,
			'exact'   => false,
			'marker'  => false,
			'address' => '',
		);
	}

	/**
	 * Geocode a human-readable address through Nominatim.
	 *
	 * @param string $address Address to geocode.
	 * @return array<string, string>|null
	 */
	private function geocode_address( string $address ): ?array {
		$queries = $this->build_geocode_queries( $address );

		foreach ( $queries as $query ) {
			$this->respect_rate_limit();

			$request_url = add_query_arg(
				array(
					'q'              => $query,
					'format'         => 'jsonv2',
					'limit'          => 1,
					'addressdetails' => 0,
				),
				self::NOMINATIM_ENDPOINT
			);

			$response = wp_remote_get(
				$request_url,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept'     => 'application/json',
						'User-Agent' => $this->get_user_agent(),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				continue;
			}

			$status_code = (int) wp_remote_retrieve_response_code( $response );

			if ( 200 !== $status_code ) {
				continue;
			}

			$body    = wp_remote_retrieve_body( $response );
			$results = json_decode( $body, true );

			if ( ! is_array( $results ) || empty( $results[0] ) || ! is_array( $results[0] ) ) {
				continue;
			}

			if ( ! $this->is_precise_enough_result( $query, $results[0] ) ) {
				continue;
			}

			$result_lat = $results[0]['lat'] ?? null;
			$result_lng = $results[0]['lon'] ?? null;

			if ( ! self::has_valid_coordinates( $result_lat, $result_lng ) ) {
				continue;
			}

			return array(
				'lat' => number_format( (float) $result_lat, 6, '.', '' ),
				'lng' => number_format( (float) $result_lng, 6, '.', '' ),
			);
		}

		return null;
	}

	/**
	 * Respect the public Nominatim rate limit.
	 *
	 * @return void
	 */
	private function respect_rate_limit(): void {
		$transient_key = 'rr_catalog_map_last_geocode_at';
		$last_request  = (float) get_transient( $transient_key );
		$now           = microtime( true );
		$elapsed       = $now - $last_request;

		if ( $last_request > 0 && $elapsed < 1 ) {
			usleep( (int) ( ( 1 - $elapsed ) * 1000000 ) );
		}

		set_transient( $transient_key, microtime( true ), MINUTE_IN_SECONDS );
	}

	/**
	 * Build a valid User-Agent for Nominatim.
	 *
	 * @return string
	 */
	private function get_user_agent(): string {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$host = 'site.local';
		}

		return 'RealtRigel/1.0 (' . $host . '; ' . home_url( '/' ) . ')';
	}

	/**
	 * Build geocoding query variants for better hit rate.
	 *
	 * @param string $address Raw address.
	 * @return string[]
	 */
	private function build_geocode_queries( string $address ): array {
		$queries   = array();
		$normalized = trim( preg_replace( '/\s+/u', ' ', $address ) );

		if ( '' === $normalized ) {
			return $queries;
		}

		$queries[] = $normalized;

		$street_prefixes = array(
			'/^ul\.\s*/iu',
			'/^ulica\s+/iu',
			'/^al\.\s*/iu',
			'/^aleja\s+/iu',
			'/^pl\.\s*/iu',
		);

		$stripped = preg_replace( $street_prefixes, '', $normalized );

		if ( is_string( $stripped ) && '' !== $stripped && $stripped !== $normalized ) {
			$queries[] = $stripped;
		}

		return array_values( array_unique( array_filter( $queries ) ) );
	}

	/**
	 * Guard against saving overly broad geocoding results.
	 *
	 * @param string               $query Geocoding query used for lookup.
	 * @param array<string, mixed> $result First Nominatim result.
	 * @return bool
	 */
	private function is_precise_enough_result( string $query, array $result ): bool {
		$house_number = $this->extract_house_number( $query );
		$display_name = isset( $result['display_name'] ) ? (string) $result['display_name'] : '';
		$name         = isset( $result['name'] ) ? (string) $result['name'] : '';
		$type         = isset( $result['type'] ) ? (string) $result['type'] : '';
		$category     = isset( $result['category'] ) ? (string) $result['category'] : '';

		if ( '' === $house_number ) {
			return true;
		}

		$haystack = mb_strtolower( $display_name . ' ' . $name );
		$needle   = mb_strtolower( $house_number );

		if ( false !== mb_strpos( $haystack, $needle ) ) {
			return true;
		}

		$street_name = $this->extract_street_name( $query );
		$city_name   = $this->extract_city_name( $query );

		if ( '' !== $street_name && $this->contains_normalized_fragment( $display_name . ' ' . $name, $street_name ) ) {
			if ( '' === $city_name || $this->contains_normalized_fragment( $display_name, $city_name ) ) {
				return true;
			}
		}

		$precise_types = array(
			'house',
			'building',
			'house_number',
			'residential',
			'commercial',
			'apartments',
			'yes',
		);

		if ( 'building' === $category && in_array( $type, $precise_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Extract house number token from address string.
	 *
	 * @param string $address Raw address.
	 * @return string
	 */
	private function extract_house_number( string $address ): string {
		if ( preg_match( '/\b(\d+[A-Za-z0-9\/-]*)\b/u', $address, $matches ) ) {
			return (string) $matches[1];
		}

		return '';
	}

	/**
	 * Extract street name from the query.
	 *
	 * @param string $address Raw address.
	 * @return string
	 */
	private function extract_street_name( string $address ): string {
		$parts = preg_split( '/\s*,\s*/u', $address );

		if ( empty( $parts[0] ) ) {
			return '';
		}

		$street = (string) $parts[0];
		$street = preg_replace(
			array(
				'/^ul\.\s*/iu',
				'/^ulica\s+/iu',
				'/^al\.\s*/iu',
				'/^aleja\s+/iu',
				'/^pl\.\s*/iu',
			),
			'',
			$street
		);
		$street = preg_replace( '/\b\d+[A-Za-z0-9\/-]*\b/u', '', (string) $street );
		$street = trim( preg_replace( '/\s+/u', ' ', (string) $street ) );

		return $street;
	}

	/**
	 * Extract city/locality chunk from the query.
	 *
	 * @param string $address Raw address.
	 * @return string
	 */
	private function extract_city_name( string $address ): string {
		$parts = preg_split( '/\s*,\s*/u', $address );

		if ( empty( $parts[1] ) ) {
			return '';
		}

		return trim( (string) $parts[1] );
	}

	/**
	 * Compare text fragments after normalization.
	 *
	 * @param string $haystack Full text.
	 * @param string $needle Fragment.
	 * @return bool
	 */
	private function contains_normalized_fragment( string $haystack, string $needle ): bool {
		$normalized_haystack = $this->normalize_text_for_match( $haystack );
		$normalized_needle   = $this->normalize_text_for_match( $needle );

		if ( '' === $normalized_haystack || '' === $normalized_needle ) {
			return false;
		}

		return false !== strpos( $normalized_haystack, $normalized_needle );
	}

	/**
	 * Normalize text for case/diacritics-insensitive matching.
	 *
	 * @param string $value Source text.
	 * @return string
	 */
	private function normalize_text_for_match( string $value ): string {
		$value = remove_accents( $value );
		$value = strtolower( $value );
		$value = preg_replace( '/\s+/u', ' ', $value );

		return trim( (string) $value );
	}

	/**
	 * Validate coordinate pair.
	 *
	 * @param mixed $lat Latitude.
	 * @param mixed $lng Longitude.
	 * @return bool
	 */
	private static function has_valid_coordinates( $lat, $lng ): bool {
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return false;
		}

		$latitude  = (float) $lat;
		$longitude = (float) $lng;

		return $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180;
	}

	/**
	 * Convert truthy stored value to boolean.
	 *
	 * @param mixed $value Raw stored value.
	 * @return bool
	 */
	private static function is_truthy( $value ): bool {
		return in_array( $value, array( true, 1, '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Build deterministic blurred coordinates for privacy mode.
	 *
	 * @param float $latitude Exact latitude.
	 * @param float $longitude Exact longitude.
	 * @param int   $post_id Post ID.
	 * @return array<string, float>
	 */
	private static function blur_coordinates( float $latitude, float $longitude, int $post_id ): array {
		$seed      = abs( crc32( 'rr-map-' . $post_id ) );
		$angle     = deg2rad( (float) ( $seed % 360 ) );
		$distance  = 0.008 + ( ( $seed % 700 ) / 100000 );
		$lat_delta = sin( $angle ) * $distance;
		$lng_scale = max( 0.2, cos( deg2rad( $latitude ) ) );
		$lng_delta = ( cos( $angle ) * $distance ) / $lng_scale;

		$blurred_lat = max( -90, min( 90, $latitude + $lat_delta ) );
		$blurred_lng = max( -180, min( 180, $longitude + $lng_delta ) );

		return array(
			'lat' => round( $blurred_lat, 6 ),
			'lng' => round( $blurred_lng, 6 ),
		);
	}
}
