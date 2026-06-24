<?php
/**
 * Referral settings service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Settings_Service {

	/**
	 * Attribution lifetime mode option key.
	 */
	public const OPTION_ATTRIBUTION_LIFETIME_MODE = 'rr_referral_attribution_lifetime_mode';

	/**
	 * Attribution lifetime days option key.
	 */
	public const OPTION_ATTRIBUTION_LIFETIME_DAYS = 'rr_referral_attribution_lifetime_days';

	/**
	 * Google Sheets export enabled option key.
	 */
	public const OPTION_GOOGLE_SHEETS_ENABLED = 'rr_referral_google_sheets_enabled';

	/**
	 * Google Sheets webhook URL option key.
	 */
	public const OPTION_GOOGLE_SHEETS_WEBHOOK_URL = 'rr_referral_google_sheets_webhook_url';

	/**
	 * Google Sheets webhook secret option key.
	 */
	public const OPTION_GOOGLE_SHEETS_WEBHOOK_SECRET = 'rr_referral_google_sheets_webhook_secret';

	/**
	 * Telegram notifications enabled option key.
	 */
	public const OPTION_TELEGRAM_ENABLED = 'rr_referral_telegram_enabled';

	/**
	 * Telegram bot token option key.
	 */
	public const OPTION_TELEGRAM_BOT_TOKEN = 'rr_referral_telegram_bot_token';

	/**
	 * Telegram chat ID option key.
	 */
	public const OPTION_TELEGRAM_CHAT_ID = 'rr_referral_telegram_chat_id';

	/**
	 * Bitrix24 lead export enabled option key.
	 */
	public const OPTION_BITRIX_ENABLED = 'rr_referral_bitrix_enabled';

	/**
	 * Bitrix24 webhook URL option key.
	 */
	public const OPTION_BITRIX_WEBHOOK_URL = 'rr_referral_bitrix_webhook_url';

	/**
	 * Bitrix24 source ID option key.
	 */
	public const OPTION_BITRIX_SOURCE_ID = 'rr_referral_bitrix_source_id';

	/**
	 * Lifetime mode: number of days.
	 */
	public const MODE_DAYS = 'days';

	/**
	 * Lifetime mode: unlimited.
	 */
	public const MODE_UNLIMITED = 'unlimited';

	/**
	 * Get attribution mode.
	 *
	 * @return string
	 */
	public function get_attribution_lifetime_mode(): string {
		$mode = get_option( self::OPTION_ATTRIBUTION_LIFETIME_MODE, self::MODE_DAYS );

		if ( ! is_string( $mode ) ) {
			return self::MODE_DAYS;
		}

		$allowed = array(
			self::MODE_DAYS,
			self::MODE_UNLIMITED,
		);

		return in_array( $mode, $allowed, true ) ? $mode : self::MODE_DAYS;
	}

	/**
	 * Get attribution lifetime days.
	 *
	 * @return int
	 */
	public function get_attribution_lifetime_days(): int {
		return max( 1, (int) get_option( self::OPTION_ATTRIBUTION_LIFETIME_DAYS, 90 ) );
	}

	/**
	 * Determine whether Google Sheets export is enabled.
	 *
	 * @return bool
	 */
	public function is_google_sheets_enabled(): bool {
		return '1' === (string) get_option( self::OPTION_GOOGLE_SHEETS_ENABLED, '0' );
	}

	/**
	 * Get Google Sheets webhook URL.
	 *
	 * @return string
	 */
	public function get_google_sheets_webhook_url(): string {
		$value = get_option( self::OPTION_GOOGLE_SHEETS_WEBHOOK_URL, '' );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Get Google Sheets webhook secret.
	 *
	 * @return string
	 */
	public function get_google_sheets_webhook_secret(): string {
		$value = get_option( self::OPTION_GOOGLE_SHEETS_WEBHOOK_SECRET, '' );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Determine whether Telegram notifications are enabled.
	 *
	 * @return bool
	 */
	public function is_telegram_enabled(): bool {
		return '1' === (string) get_option( self::OPTION_TELEGRAM_ENABLED, '0' );
	}

	/**
	 * Get Telegram bot token.
	 *
	 * @return string
	 */
	public function get_telegram_bot_token(): string {
		$value = get_option( self::OPTION_TELEGRAM_BOT_TOKEN, '' );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Get Telegram chat ID.
	 *
	 * @return string
	 */
	public function get_telegram_chat_id(): string {
		$value = get_option( self::OPTION_TELEGRAM_CHAT_ID, '' );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Determine whether Bitrix24 lead export is enabled.
	 *
	 * @return bool
	 */
	public function is_bitrix_enabled(): bool {
		return '1' === (string) get_option( self::OPTION_BITRIX_ENABLED, '0' );
	}

	/**
	 * Get Bitrix24 webhook URL.
	 *
	 * @return string
	 */
	public function get_bitrix_webhook_url(): string {
		$value = get_option( self::OPTION_BITRIX_WEBHOOK_URL, '' );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Get Bitrix24 source ID.
	 *
	 * @return string
	 */
	public function get_bitrix_source_id(): string {
		$value = get_option( self::OPTION_BITRIX_SOURCE_ID, 'WEB' );
		$value = is_string( $value ) ? trim( $value ) : 'WEB';

		return '' !== $value ? $value : 'WEB';
	}
}
