<?php
/**
 * Referral lead repository.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Lead_Repository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	private function table_name(): string {
		return rr_referral_table_name( 'leads' );
	}

	/**
	 * Create lead row.
	 *
	 * @param array<string, mixed> $data Insert data.
	 * @return int
	 */
	public function create( array $data ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$defaults = array(
			'partner_id'              => 0,
			'visit_id'                => null,
			'source_type'             => 'form',
			'contact_name'            => '',
			'contact_phone'           => '',
			'contact_email'           => '',
			'contact_telegram'        => '',
			'contact_message'         => '',
			'catalog_post_id'         => null,
			'object_title'            => '',
			'object_url'              => '',
			'redirect_url'            => '',
			'language'                => '',
			'user_agent'              => '',
			'status'                  => 'new',
			'bitrix_lead_id'          => '',
			'bitrix_status_id'        => '',
			'bitrix_status_label'     => '',
			'bitrix_synced_at'        => null,
			'attribution_model'       => 'first_click',
			'attribution_expires_at'  => null,
			'created_at'              => $now,
			'updated_at'              => $now,
		);

		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( $this->table_name(), $data );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get lead by id.
	 *
	 * @param int $id Lead id.
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
	 * Update lead row.
	 *
	 * @param int                  $id   Lead id.
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
	 * Delete lead row.
	 *
	 * @param int $id Lead id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get leads for partner.
	 *
	 * @param int $partner_id Partner id.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_partner_id( int $partner_id, int $per_page = 20, int $page = 1 ): array {
		global $wpdb;

		$per_page = max( 1, $per_page );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name()} WHERE partner_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$partner_id,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * List all leads.
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
	 * List all leads for export.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_all_for_export(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT * FROM {$this->table_name()} ORDER BY created_at DESC, id DESC",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count all leads for a partner.
	 *
	 * @param int $partner_id Partner id.
	 * @return int
	 */
	public function count_by_partner_id( int $partner_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name()} WHERE partner_id = %d",
				$partner_id
			)
		);
	}

	/**
	 * Get aggregated status counts for a partner.
	 *
	 * @param int $partner_id Partner id.
	 * @return array<string, int>
	 */
	public function get_status_counts_by_partner_id( int $partner_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$this->table_name()} WHERE partner_id = %d GROUP BY status",
				$partner_id
			),
			ARRAY_A
		);

		$stats = array(
			'total'       => 0,
			'new'         => 0,
			'in_progress' => 0,
			'deal'        => 0,
		);

		if ( ! is_array( $rows ) ) {
			return $stats;
		}

		foreach ( $rows as $row ) {
			$status = isset( $row['status'] ) ? (string) $row['status'] : '';
			$total  = isset( $row['total'] ) ? (int) $row['total'] : 0;

			$stats['total'] += $total;

			if ( isset( $stats[ $status ] ) ) {
				$stats[ $status ] = $total;
			}
		}

		return $stats;
	}

	/**
	 * Count all leads.
	 *
	 * @return int
	 */
	public function count_all(): int {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name()}" );
	}

	/**
	 * Find recent matching lead for deduplication.
	 *
	 * @param int    $partner_id Partner id.
	 * @param string $source_type Source type.
	 * @param string $contact_phone Contact phone.
	 * @param string $contact_email Contact email.
	 * @param string $contact_telegram Contact telegram.
	 * @param int    $window_minutes Deduplication window in minutes.
	 * @return array<string, mixed>|null
	 */
	public function find_recent_duplicate(
		int $partner_id,
		string $source_type,
		string $contact_phone,
		string $contact_email,
		string $contact_telegram,
		int $window_minutes = 10
	): ?array {
		global $wpdb;

		$contact_phone    = trim( $contact_phone );
		$contact_email    = trim( $contact_email );
		$contact_telegram = trim( $contact_telegram );

		if ( '' === $contact_phone && '' === $contact_email && '' === $contact_telegram ) {
			return null;
		}

		$threshold = gmdate( 'Y-m-d H:i:s', time() - ( max( 1, $window_minutes ) * MINUTE_IN_SECONDS ) );
		$conditions = array();
		$params     = array( $partner_id, $source_type, $threshold );

		if ( '' !== $contact_phone ) {
			$conditions[] = 'contact_phone = %s';
			$params[]     = $contact_phone;
		}

		if ( '' !== $contact_email ) {
			$conditions[] = 'contact_email = %s';
			$params[]     = $contact_email;
		}

		if ( '' !== $contact_telegram ) {
			$conditions[] = 'contact_telegram = %s';
			$params[]     = $contact_telegram;
		}

		if ( empty( $conditions ) ) {
			return null;
		}

		$sql = "
			SELECT *
			FROM {$this->table_name()}
			WHERE partner_id = %d
				AND source_type = %s
				AND created_at >= %s
				AND (" . implode( ' OR ', $conditions ) . ')
			ORDER BY id DESC
			LIMIT 1
		';

		$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $row ) ? $row : null;
	}
}
