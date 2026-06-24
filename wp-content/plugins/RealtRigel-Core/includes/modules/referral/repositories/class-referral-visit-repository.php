<?php
/**
 * Referral visit repository.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Visit_Repository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private function table_name(): string {
		return rr_referral_table_name( 'visits' );
	}

	/**
	 * Create visit row.
	 *
	 * @param array<string, mixed> $data Insert data.
	 * @return int
	 */
	public function create( array $data ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$defaults = array(
			'partner_id'       => 0,
			'session_key'      => '',
			'landing_url'      => null,
			'referrer_url'     => null,
			'utm_source'       => '',
			'utm_medium'       => '',
			'utm_campaign'     => '',
			'ip_hash'          => '',
			'user_agent_hash'  => '',
			'first_seen_at'    => $now,
			'last_seen_at'     => $now,
			'created_at'       => $now,
			'updated_at'       => $now,
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( $this->table_name(), $data );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get visit by id.
	 *
	 * @param int $id Visit id.
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
	 * Get latest visit by session key.
	 *
	 * @param string $session_key Session key.
	 * @return array<string, mixed>|null
	 */
	public function get_latest_by_session_key( string $session_key ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name()} WHERE session_key = %s ORDER BY id DESC LIMIT 1",
				$session_key
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Update visit row.
	 *
	 * @param int                  $id   Visit id.
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
}
