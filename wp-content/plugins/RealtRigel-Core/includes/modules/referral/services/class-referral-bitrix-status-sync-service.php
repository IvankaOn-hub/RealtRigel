<?php
/**
 * Bitrix24 lead status sync service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Bitrix_Status_Sync_Service {

	/**
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Lead repository.
	 *
	 * @var RR_Referral_Lead_Repository
	 */
	private RR_Referral_Lead_Repository $lead_repository;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Settings_Service $settings_service Settings service.
	 * @param RR_Referral_Lead_Repository  $lead_repository Lead repository.
	 */
	public function __construct( RR_Referral_Settings_Service $settings_service, RR_Referral_Lead_Repository $lead_repository ) {
		$this->settings_service = $settings_service;
		$this->lead_repository  = $lead_repository;
	}

	/**
	 * Sync Bitrix statuses for visible leads.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return void
	 */
	public function sync_leads( array $leads ): void {
		if ( ! $this->settings_service->is_bitrix_enabled() ) {
			return;
		}

		$ids = $this->get_syncable_bitrix_ids( $leads );

		if ( empty( $ids ) ) {
			return;
		}

		$endpoint_url = $this->build_endpoint_url( $this->settings_service->get_bitrix_webhook_url(), 'crm.lead.list.json' );

		if ( '' === $endpoint_url ) {
			return;
		}

		$response = wp_remote_post(
			$endpoint_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode(
					array(
						'FILTER' => array(
							'@ID' => $ids,
						),
						'SELECT' => array( 'ID', 'STATUS_ID', 'DATE_MODIFY' ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Bitrix24 status sync failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			error_log( 'Bitrix24 status sync failed with HTTP ' . $status_code . ': ' . $body ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || isset( $data['error'] ) || empty( $data['result'] ) || ! is_array( $data['result'] ) ) {
			error_log( 'Bitrix24 status sync failed: ' . $body ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$lead_map = $this->get_local_lead_id_map( $leads );

		foreach ( $data['result'] as $bitrix_lead ) {
			if ( ! is_array( $bitrix_lead ) || empty( $bitrix_lead['ID'] ) ) {
				continue;
			}

			$bitrix_id = (string) $bitrix_lead['ID'];

			if ( empty( $lead_map[ $bitrix_id ] ) ) {
				continue;
			}

			$status_id = isset( $bitrix_lead['STATUS_ID'] ) ? (string) $bitrix_lead['STATUS_ID'] : '';

			$this->lead_repository->update(
				(int) $lead_map[ $bitrix_id ],
				array(
					'status'              => $this->map_to_site_status( $status_id ),
					'bitrix_status_id'    => $status_id,
					'bitrix_status_label' => $this->format_status_label( $status_id ),
					'bitrix_synced_at'    => current_time( 'mysql', true ),
				)
			);
		}
	}

	/**
	 * Get Bitrix IDs that should be synced now.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return string[]
	 */
	private function get_syncable_bitrix_ids( array $leads ): array {
		$ids = array();

		foreach ( $leads as $lead ) {
			$bitrix_id = isset( $lead['bitrix_lead_id'] ) ? trim( (string) $lead['bitrix_lead_id'] ) : '';

			if ( '' === $bitrix_id || ! $this->should_sync_lead( $lead ) ) {
				continue;
			}

			$ids[] = $bitrix_id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Determine whether a lead should be synced.
	 *
	 * @param array<string, mixed> $lead Lead.
	 * @return bool
	 */
	private function should_sync_lead( array $lead ): bool {
		$synced_at = isset( $lead['bitrix_synced_at'] ) ? (string) $lead['bitrix_synced_at'] : '';

		if ( '' === $synced_at ) {
			return true;
		}

		$timestamp = strtotime( $synced_at );

		return false === $timestamp || ( time() - $timestamp ) > ( 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Get local lead ID map by Bitrix ID.
	 *
	 * @param array<int, array<string, mixed>> $leads Leads.
	 * @return array<string, int>
	 */
	private function get_local_lead_id_map( array $leads ): array {
		$map = array();

		foreach ( $leads as $lead ) {
			$local_id  = isset( $lead['id'] ) ? (int) $lead['id'] : 0;
			$bitrix_id = isset( $lead['bitrix_lead_id'] ) ? trim( (string) $lead['bitrix_lead_id'] ) : '';

			if ( $local_id > 0 && '' !== $bitrix_id ) {
				$map[ $bitrix_id ] = $local_id;
			}
		}

		return $map;
	}

	/**
	 * Normalize Bitrix24 webhook URL to a method endpoint.
	 *
	 * @param string $webhook_url Raw webhook URL.
	 * @param string $method Bitrix REST method.
	 * @return string
	 */
	private function build_endpoint_url( string $webhook_url, string $method ): string {
		$webhook_url = trim( $webhook_url );

		if ( '' === $webhook_url ) {
			return '';
		}

		if ( false !== strpos( $webhook_url, 'crm.' ) ) {
			$webhook_url = preg_replace( '~crm\.[a-z.]+\.json$~', '', $webhook_url );
			$webhook_url = is_string( $webhook_url ) ? $webhook_url : '';
		}

		return trailingslashit( $webhook_url ) . $method;
	}

	/**
	 * Format known Bitrix status label.
	 *
	 * @param string $status_id Bitrix status ID.
	 * @return string
	 */
	private function format_status_label( string $status_id ): string {
		$map = array(
			'NEW'        => 'Новый',
			'IN_PROCESS' => 'В работе',
			'PROCESSED'  => 'Обработан',
			'CONVERTED'  => 'Сконвертирован',
			'JUNK'       => 'Некачественный',
		);

		return $map[ $status_id ] ?? $status_id;
	}

	/**
	 * Map Bitrix status to local lead status.
	 *
	 * @param string $status_id Bitrix status ID.
	 * @return string
	 */
	private function map_to_site_status( string $status_id ): string {
		$map = array(
			'NEW'        => 'new',
			'IN_PROCESS' => 'in_progress',
			'PROCESSED'  => 'qualified',
			'CONVERTED'  => 'deal',
			'JUNK'       => 'rejected',
		);

		return $map[ $status_id ] ?? 'in_progress';
	}
}
