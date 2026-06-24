<?php
/**
 * Bitrix24 lead export hooks.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Bitrix_Lead_Export_Hooks {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rr_referral_lead_created', array( $this, 'export_lead' ), 10, 3 );
	}

	/**
	 * Export lead to Bitrix24.
	 *
	 * @param int                  $lead_id Lead id.
	 * @param array<string, mixed> $lead_data Saved lead data.
	 * @param array<string, mixed> $payload Original form payload.
	 * @return void
	 */
	public function export_lead( int $lead_id, array $lead_data, array $payload ): void {
		if ( ! $this->settings_service->is_bitrix_enabled() ) {
			return;
		}

		$webhook_url = $this->build_endpoint_url( $this->settings_service->get_bitrix_webhook_url() );

		if ( '' === $webhook_url ) {
			return;
		}

		$response = wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode(
					array(
						'fields' => $this->build_fields( $lead_data, $payload ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Bitrix24 lead export failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			error_log( 'Bitrix24 lead export failed with HTTP ' . $status_code . ': ' . $body ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || isset( $data['error'] ) ) {
			error_log( 'Bitrix24 lead export failed: ' . $body ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$bitrix_lead_id = isset( $data['result'] ) ? (string) $data['result'] : '';

		if ( '' !== $bitrix_lead_id ) {
			$this->lead_repository->update(
				$lead_id,
				array(
					'bitrix_lead_id' => $bitrix_lead_id,
				)
			);
		}
	}

	/**
	 * Build Bitrix24 lead fields.
	 *
	 * @param array<string, mixed> $lead_data Saved lead data.
	 * @param array<string, mixed> $payload Original form payload.
	 * @return array<string, mixed>
	 */
	private function build_fields( array $lead_data, array $payload ): array {
		$object_title = (string) ( $lead_data['object_title'] ?? '' );
		$name         = (string) ( $lead_data['contact_name'] ?? '' );
		$phone        = (string) ( $lead_data['contact_phone'] ?? '' );
		$email        = (string) ( $lead_data['contact_email'] ?? '' );

		$fields = array(
			'TITLE'     => '' !== $object_title ? 'Заявка с сайта: ' . $object_title : 'Заявка с сайта',
			'NAME'      => '' !== $name ? $name : 'Клиент',
			'COMMENTS'  => $this->build_comments( $lead_data, $payload ),
			'SOURCE_ID' => $this->settings_service->get_bitrix_source_id(),
		);

		if ( '' !== $phone ) {
			$fields['PHONE'] = array(
				array(
					'VALUE'      => $phone,
					'VALUE_TYPE' => 'WORK',
				),
			);
		}

		if ( '' !== $email ) {
			$fields['EMAIL'] = array(
				array(
					'VALUE'      => $email,
					'VALUE_TYPE' => 'WORK',
				),
			);
		}

		return $fields;
	}

	/**
	 * Build Bitrix24 comments field.
	 *
	 * @param array<string, mixed> $lead_data Saved lead data.
	 * @param array<string, mixed> $payload Original form payload.
	 * @return string
	 */
	private function build_comments( array $lead_data, array $payload ): string {
		$lines = array(
			'Сообщение: ' . $this->get_value( $lead_data, 'contact_message' ),
			'Telegram: ' . $this->get_value( $lead_data, 'contact_telegram' ),
			'Объект: ' . $this->get_value( $lead_data, 'object_title' ),
			'Ссылка на объект: ' . $this->get_value( $lead_data, 'object_url' ),
			'Язык: ' . $this->get_value( $lead_data, 'language' ),
			'User Agent: ' . $this->get_value( $lead_data, 'user_agent' ),
			'Партнер: ' . $this->get_payload_value( $payload, 'partner_code' ),
			'Источник: ' . $this->get_payload_value( $payload, 'source' ),
			'Redirect URL: ' . $this->get_value( $lead_data, 'redirect_url' ),
		);

		return implode( "\n", $lines );
	}

	/**
	 * Normalize Bitrix24 webhook URL to crm.lead.add endpoint.
	 *
	 * @param string $webhook_url Raw webhook URL.
	 * @return string
	 */
	private function build_endpoint_url( string $webhook_url ): string {
		$webhook_url = trim( $webhook_url );

		if ( '' === $webhook_url ) {
			return '';
		}

		if ( false !== strpos( $webhook_url, 'crm.lead.add' ) ) {
			return $webhook_url;
		}

		return trailingslashit( $webhook_url ) . 'crm.lead.add.json';
	}

	/**
	 * Get lead data value.
	 *
	 * @param array<string, mixed> $data Data.
	 * @param string               $key Key.
	 * @return string
	 */
	private function get_value( array $data, string $key ): string {
		$value = isset( $data[ $key ] ) ? trim( (string) $data[ $key ] ) : '';

		return '' !== $value ? $value : '-';
	}

	/**
	 * Get payload value.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @param string               $key Key.
	 * @return string
	 */
	private function get_payload_value( array $payload, string $key ): string {
		$value = isset( $payload[ $key ] ) ? trim( (string) $payload[ $key ] ) : '';

		return '' !== $value ? $value : '-';
	}
}
