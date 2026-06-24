<?php
/**
 * Referral partner repository.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Partner_Repository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private function table_name(): string {
		return rr_referral_table_name( 'partners' );
	}

	/**
	 * Create partner row.
	 *
	 * @param array<string, mixed> $data Insert data.
	 * @return int
	 */
	public function create( array $data ): int {
		global $wpdb;

		$defaults = array(
			'user_id'      => 0,
			'partner_code' => '',
			'status'       => 'active',
			'created_at'   => current_time( 'mysql', true ),
			'updated_at'   => current_time( 'mysql', true ),
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert(
			$this->table_name(),
			$data,
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get partner by id.
	 *
	 * @param int $id Partner id.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE id = %d", $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get partner by user id.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>|null
	 */
	public function get_by_user_id( int $user_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE user_id = %d", $user_id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get partner by referral code.
	 *
	 * @param string $partner_code Partner code.
	 * @return array<string, mixed>|null
	 */
	public function get_by_partner_code( string $partner_code ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE partner_code = %s", $partner_code ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get partners by ids.
	 *
	 * @param array<int, int> $ids Partner ids.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_by_ids( array $ids ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = $wpdb->prepare(
			"SELECT * FROM {$this->table_name()} WHERE id IN ($placeholders)",
			...$ids
		);
		$rows         = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Update partner row.
	 *
	 * @param int                  $id   Partner id.
	 * @param array<string, mixed> $data Update data.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql', true );

		$result = $wpdb->update(
			$this->table_name(),
			$data,
			array( 'id' => $id )
		);

		return false !== $result;
	}

	/**
	 * List all partners.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_all( int $per_page = 20, int $page = 1 ): array {
		global $wpdb;

		$per_page = max( 1, $per_page );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name()} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count all partners.
	 *
	 * @return int
	 */
	public function count_all(): int {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name()}" );
	}
}
