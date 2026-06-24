<?php
/**
 * Catalog required fields validation service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Requirements_Validator {

	/**
	 * Supported location levels.
	 *
	 * @var string[]
	 */
	private array $levels = array( 'country', 'region', 'city', 'district' );

	/**
	 * Get missing required fields from current request.
	 *
	 * @return string[]
	 */
	public function get_missing_fields_from_request(): array {
		$missing = array();

		if ( $this->get_submitted_term_id( 'rr_property_type_term_id' ) <= 0 ) {
			$missing[] = __( 'Тип недвижимости', 'realtrigel-core' );
		}

		if ( $this->get_submitted_term_id( 'rr_property_deal_type_term_id' ) <= 0 ) {
			$missing[] = __( 'Тип сделки', 'realtrigel-core' );
		}

		if ( $this->is_empty_location_data( $this->build_consistent_location_data_from_request() ) ) {
			$missing[] = __( 'Локация', 'realtrigel-core' );
		}

		return $missing;
	}

	/**
	 * Get missing required fields for saved post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string[]
	 */
	public function get_missing_fields_for_post( int $post_id ): array {
		$missing = array();

		$taxonomy_labels = array(
			RR_Property_Type_Taxonomy::TAXONOMY      => __( 'Тип недвижимости', 'realtrigel-core' ),
			RR_Property_Deal_Type_Taxonomy::TAXONOMY => __( 'Тип сделки', 'realtrigel-core' ),
			RR_Property_Location_Taxonomy::TAXONOMY  => __( 'Локация', 'realtrigel-core' ),
		);

		foreach ( $taxonomy_labels as $taxonomy => $label ) {
			$term_ids = wp_get_object_terms(
				$post_id,
				$taxonomy,
				array(
					'fields' => 'ids',
					'number' => 1,
				)
			);

			if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
				$missing[] = $label;
			}
		}

		return $missing;
	}

	/**
	 * Get missing required fields by combining current request and saved post state.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string[]
	 */
	public function get_missing_fields_from_request_or_post( int $post_id ): array {
		$missing = array();

		$property_type_request = $this->get_submitted_term_id( 'rr_property_type_term_id' ) > 0;
		$property_type_saved   = $this->post_has_terms( $post_id, RR_Property_Type_Taxonomy::TAXONOMY );
		if ( ! $property_type_request && ! $property_type_saved ) {
			$missing[] = __( 'Тип недвижимости', 'realtrigel-core' );
		}

		$deal_type_request = $this->get_submitted_term_id( 'rr_property_deal_type_term_id' ) > 0;
		$deal_type_saved   = $this->post_has_terms( $post_id, RR_Property_Deal_Type_Taxonomy::TAXONOMY );
		if ( ! $deal_type_request && ! $deal_type_saved ) {
			$missing[] = __( 'Тип сделки', 'realtrigel-core' );
		}

		$location_request = ! $this->is_empty_location_data( $this->build_consistent_location_data_from_request() );
		$location_saved   = $this->post_has_terms( $post_id, RR_Property_Location_Taxonomy::TAXONOMY );
		if ( ! $location_request && ! $location_saved ) {
			$missing[] = __( 'Локация', 'realtrigel-core' );
		}

		return $missing;
	}

	/**
	 * Get missing required fields from REST request payload.
	 *
	 * @param \WP_REST_Request $request REST request.
	 *
	 * @return string[]
	 */
	public function get_missing_fields_from_rest_request( \WP_REST_Request $request ): array {
		$missing = array();

		$has_property_types = null !== $request->get_param( RR_Property_Type_Taxonomy::TAXONOMY );
		$property_types     = $request->get_param( RR_Property_Type_Taxonomy::TAXONOMY );
		if ( $has_property_types && ( ! is_array( $property_types ) || empty( array_filter( array_map( 'absint', $property_types ) ) ) ) ) {
			$missing[] = __( 'Тип недвижимости', 'realtrigel-core' );
		}

		$has_deal_types = null !== $request->get_param( RR_Property_Deal_Type_Taxonomy::TAXONOMY );
		$deal_types     = $request->get_param( RR_Property_Deal_Type_Taxonomy::TAXONOMY );
		if ( $has_deal_types && ( ! is_array( $deal_types ) || empty( array_filter( array_map( 'absint', $deal_types ) ) ) ) ) {
			$missing[] = __( 'Тип сделки', 'realtrigel-core' );
		}

		$has_locations = null !== $request->get_param( RR_Property_Location_Taxonomy::TAXONOMY );
		$locations     = $request->get_param( RR_Property_Location_Taxonomy::TAXONOMY );
		if ( $has_locations && ( ! is_array( $locations ) || empty( array_filter( array_map( 'absint', $locations ) ) ) ) ) {
			$missing[] = __( 'Локация', 'realtrigel-core' );
		}

		return $missing;
	}

	/**
	 * Build consistent location data by validating parent-child chain.
	 *
	 * @return array<string,string>
	 */
	public function build_consistent_location_data_from_request(): array {
		$data = array(
			'country'  => '',
			'region'   => '',
			'city'     => '',
			'district' => '',
		);

		$parent_term_id = 0;

		foreach ( $this->levels as $level ) {
			$resolved = $this->resolve_level_submission( $level, $parent_term_id );

			if ( '' === $resolved['name'] ) {
				break;
			}

			$data[ $level ] = $resolved['name'];

			if ( $resolved['is_existing'] ) {
				$parent_term_id = $resolved['term_id'];
				continue;
			}

			$parent_term_id = 0;
		}

		return $data;
	}

	/**
	 * Check whether location data is empty.
	 *
	 * @param array<string,string> $data Location data.
	 *
	 * @return bool
	 */
	public function is_empty_location_data( array $data ): bool {
		foreach ( $this->levels as $level ) {
			if ( isset( $data[ $level ] ) && '' !== trim( $data[ $level ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Detect background saves.
	 *
	 * @param array<string,mixed> $postarr Raw post data.
	 *
	 * @return bool
	 */
	public function is_background_save( array $postarr ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		if ( isset( $postarr['ID'] ) ) {
			$post_id = (int) $postarr['ID'];

			if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether current request looks like the editor form submission.
	 *
	 * @return bool
	 */
	public function has_editor_submission(): bool {
		return isset( $_POST['rr_property_type_term_id'] )
			|| isset( $_POST['rr_property_deal_type_term_id'] )
			|| isset( $_POST['rr_location_existing_country'] )
			|| isset( $_POST['rr_location_new_country'] )
			|| isset( $_POST['rr_property_classification_fields_nonce'] )
			|| isset( $_POST['rr_property_location_fields_nonce'] );
	}

	/**
	 * Determine whether current request is the Gutenberg meta box loader.
	 *
	 * @return bool
	 */
	public function is_meta_box_loader_request(): bool {
		return isset( $_REQUEST['meta-box-loader'] ) && '1' === (string) wp_unslash( $_REQUEST['meta-box-loader'] );
	}

	/**
	 * Determine whether current request is a REST request.
	 *
	 * @return bool
	 */
	public function is_rest_request(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Resolve submitted level value and validate relationship with parent.
	 *
	 * @param string $level           Level key.
	 * @param int    $expected_parent Expected parent term ID for existing selection.
	 *
	 * @return array{name:string,term_id:int,is_existing:bool}
	 */
	private function resolve_level_submission( string $level, int $expected_parent ): array {
		$new_key      = 'rr_location_new_' . $level;
		$existing_key = 'rr_location_existing_' . $level;

		$new_value = '';
		if ( isset( $_POST[ $new_key ] ) ) {
			$new_value = sanitize_text_field( wp_unslash( $_POST[ $new_key ] ) );
			$new_value = trim( $new_value );
		}

		if ( '' !== $new_value ) {
			return array(
				'name'        => $new_value,
				'term_id'     => 0,
				'is_existing' => false,
			);
		}

		if ( ! isset( $_POST[ $existing_key ] ) ) {
			return array(
				'name'        => '',
				'term_id'     => 0,
				'is_existing' => false,
			);
		}

		$existing_id = absint( wp_unslash( $_POST[ $existing_key ] ) );
		if ( $existing_id <= 0 ) {
			return array(
				'name'        => '',
				'term_id'     => 0,
				'is_existing' => false,
			);
		}

		$term = get_term( $existing_id, RR_Property_Location_Taxonomy::TAXONOMY );
		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'name'        => '',
				'term_id'     => 0,
				'is_existing' => false,
			);
		}

		if ( (int) $term->parent !== $expected_parent ) {
			return array(
				'name'        => '',
				'term_id'     => 0,
				'is_existing' => false,
			);
		}

		return array(
			'name'        => $term->name,
			'term_id'     => (int) $term->term_id,
			'is_existing' => true,
		);
	}

	/**
	 * Get submitted term ID.
	 *
	 * @param string $input_name Input field name.
	 *
	 * @return int
	 */
	private function get_submitted_term_id( string $input_name ): int {
		if ( ! isset( $_POST[ $input_name ] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_POST[ $input_name ] ) );
	}

	/**
	 * Determine whether a post has at least one term in taxonomy.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return bool
	 */
	private function post_has_terms( int $post_id, string $taxonomy ): bool {
		$term_ids = wp_get_object_terms(
			$post_id,
			$taxonomy,
			array(
				'fields' => 'ids',
				'number' => 1,
			)
		);

		return ! is_wp_error( $term_ids ) && ! empty( $term_ids );
	}
}
