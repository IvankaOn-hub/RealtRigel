<?php
/**
 * Referral lead status history repository.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Lead_Status_History_Repository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private function table_name(): string {
		return rr_referral_table_name( 'lead_status_history' );
	}

	/**
	 * Create history row.
	 *
	 * @param array<string, mixed> $data Insert data.
	 * @return int
	 */
	public function create( array $data ): int {
		global $wpdb;

		$defaults = array(
			'lead_id'     => 0,
			'old_status'  => '',
			'new_status'  => 'new',
			'source'      => 'site',
			'comment'     => null,
			'created_at'  => current_time( 'mysql', true ),
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( $this->table_name(), $data );

		return (int) $wpdb->insert_id;
	}

	/**
	 * List history rows for lead.
	 *
	 * @param int $lead_id Lead id.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_lead_id( int $lead_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name()} WHERE lead_id = %d ORDER BY created_at ASC, id ASC",
				$lead_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Delete history rows for lead.
	 *
	 * @param int $lead_id Lead id.
	 * @return bool
	 */
	public function delete_by_lead_id( int $lead_id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name(),
			array( 'lead_id' => $lead_id ),
			array( '%d' )
		);

		return false !== $result;
	}
}
