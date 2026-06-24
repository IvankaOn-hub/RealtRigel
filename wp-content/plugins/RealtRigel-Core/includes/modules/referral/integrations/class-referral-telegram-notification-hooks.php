<?php
/**
 * Telegram lead notification hooks.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Telegram_Notification_Hooks {

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
		add_action( 'realtrigel_property_contact_validated', array( $this, 'send_property_contact_notification' ), 10, 1 );
	}

	/**
	 * Send property contact notification to Telegram.
	 *
	 * @param array<string, mixed> $payload Form payload.
	 * @return void
	 */
	public function send_property_contact_notification( array $payload ): void {
		if ( ! $this->settings_service->is_telegram_enabled() ) {
			return;
		}

		$bot_token = $this->settings_service->get_telegram_bot_token();
		$chat_id   = $this->settings_service->get_telegram_chat_id();

		if ( '' === $bot_token || '' === $chat_id ) {
			return;
		}

		$response = wp_remote_post(
			'https://api.telegram.org/bot' . rawurlencode( $bot_token ) . '/sendMessage',
			array(
				'timeout' => 8,
				'body'    => array(
					'chat_id'                  => $chat_id,
					'text'                     => $this->build_message( $payload ),
					'parse_mode'               => 'HTML',
					'disable_web_page_preview' => 'true',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Telegram lead notification failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Build Telegram message text.
	 *
	 * @param array<string, mixed> $payload Form payload.
	 * @return string
	 */
	private function build_message( array $payload ): string {
		$object_title = $this->get_payload_value( $payload, 'post_title' );
		$object_url   = $this->get_payload_value( $payload, 'object_url' );
		$name         = $this->get_payload_value( $payload, 'name' );
		$phone        = $this->get_payload_value( $payload, 'phone' );
		$email        = $this->get_payload_value( $payload, 'email' );
		$telegram     = $this->get_payload_value( $payload, 'telegram' );
		$message      = $this->get_payload_value( $payload, 'message' );
		$partner_code = $this->get_payload_value( $payload, 'partner_code' );
		$language     = $this->get_payload_value( $payload, 'language' );

		$lines = array(
			'<b>New property request</b>',
			'',
			'<b>Object:</b> ' . esc_html( '' !== $object_title ? $object_title : '-' ),
			'<b>URL:</b> ' . esc_html( '' !== $object_url ? $object_url : '-' ),
			'',
			'<b>Name:</b> ' . esc_html( '' !== $name ? $name : '-' ),
			'<b>Phone:</b> ' . esc_html( '' !== $phone ? $phone : '-' ),
			'<b>Email:</b> ' . esc_html( '' !== $email ? $email : '-' ),
			'<b>Telegram:</b> ' . esc_html( '' !== $telegram ? $telegram : '-' ),
			'<b>Message:</b> ' . esc_html( '' !== $message ? $message : '-' ),
			'',
			'<b>Partner code:</b> ' . esc_html( '' !== $partner_code ? $partner_code : '-' ),
			'<b>Language:</b> ' . esc_html( '' !== $language ? $language : '-' ),
		);

		return implode( "\n", $lines );
	}

	/**
	 * Get scalar payload value.
	 *
	 * @param array<string, mixed> $payload Form payload.
	 * @param string               $key     Payload key.
	 * @return string
	 */
	private function get_payload_value( array $payload, string $key ): string {
		return isset( $payload[ $key ] ) && is_scalar( $payload[ $key ] ) ? (string) $payload[ $key ] : '';
	}
}
