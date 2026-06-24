<?php
/**
 * Google Sheets lead export hooks.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Google_Sheets_Export_Hooks {

	/**
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Settings_Service $settings_service Settings service.
	 */
	public function __construct( RR_Referral_Settings_Service $settings_service ) {
		$this->settings_service = $settings_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'realtrigel_property_contact_validated', array( $this, 'export_property_contact' ), 10, 1 );
	}

	/**
	 * Export property contact payload to Google Sheets webhook.
	 *
	 * @param array<string, mixed> $payload Form payload.
	 * @return void
	 */
	public function export_property_contact( array $payload ): void {
		if ( ! $this->settings_service->is_google_sheets_enabled() ) {
			return;
		}

		$webhook_url = $this->settings_service->get_google_sheets_webhook_url();

		if ( '' === $webhook_url ) {
			return;
		}

		$body = array(
			'date'         => current_time( 'mysql' ),
			'object_id'    => isset( $payload['post_id'] ) ? (int) $payload['post_id'] : 0,
			'object_title' => isset( $payload['post_title'] ) ? (string) $payload['post_title'] : '',
			'object_url'   => isset( $payload['object_url'] ) ? (string) $payload['object_url'] : '',
			'name'         => isset( $payload['name'] ) ? (string) $payload['name'] : '',
			'phone'        => isset( $payload['phone'] ) ? (string) $payload['phone'] : '',
			'email'        => isset( $payload['email'] ) ? (string) $payload['email'] : '',
			'telegram'     => isset( $payload['telegram'] ) ? (string) $payload['telegram'] : '',
			'message'      => isset( $payload['message'] ) ? (string) $payload['message'] : '',
			'partner_code' => isset( $payload['partner_code'] ) ? (string) $payload['partner_code'] : '',
			'source'       => isset( $payload['source'] ) ? (string) $payload['source'] : '',
			'redirect_url' => isset( $payload['redirect_url'] ) ? (string) $payload['redirect_url'] : '',
			'language'     => isset( $payload['language'] ) ? (string) $payload['language'] : '',
			'user_agent'   => isset( $payload['user_agent'] ) ? (string) $payload['user_agent'] : '',
		);

		$secret = $this->settings_service->get_google_sheets_webhook_secret();

		if ( '' !== $secret ) {
			$body['secret'] = $secret;
		}

		$response = wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 8,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Google Sheets lead export failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			error_log( 'Google Sheets lead export failed with HTTP ' . $status_code . ': ' . wp_remote_retrieve_body( $response ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
