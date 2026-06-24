<?php
/**
 * Referral first-click tracking service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Tracking_Service {

	/**
	 * Partner cookie name.
	 */
	public const COOKIE_PARTNER_CODE = 'rr_referral_partner';

	/**
	 * Visitor session cookie name.
	 */
	public const COOKIE_SESSION_KEY = 'rr_referral_session';

	/**
	 * Partner repository.
	 *
	 * @var RR_Referral_Partner_Repository
	 */
	private RR_Referral_Partner_Repository $partner_repository;

	/**
	 * Visit repository.
	 *
	 * @var RR_Referral_Visit_Repository
	 */
	private RR_Referral_Visit_Repository $visit_repository;

	/**
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Repository $partner_repository Partner repository.
	 * @param RR_Referral_Visit_Repository   $visit_repository Visit repository.
	 * @param RR_Referral_Settings_Service   $settings_service Settings service.
	 */
	public function __construct( RR_Referral_Partner_Repository $partner_repository, RR_Referral_Visit_Repository $visit_repository, RR_Referral_Settings_Service $settings_service ) {
		$this->partner_repository = $partner_repository;
		$this->visit_repository   = $visit_repository;
		$this->settings_service   = $settings_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'template_redirect', array( $this, 'capture_referral_visit' ), 5 );
	}

	/**
	 * Capture first-click referral on frontend requests.
	 *
	 * @return void
	 */
	public function capture_referral_visit(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		$partner_code = isset( $_GET[ RR_Referral_Link_Service::QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ RR_Referral_Link_Service::QUERY_VAR ] ) ) : '';

		if ( '' === $partner_code ) {
			return;
		}

		$partner = $this->partner_repository->get_by_partner_code( $partner_code );

		if ( ! is_array( $partner ) || empty( $partner['id'] ) ) {
			return;
		}

		$current_partner_code = $this->get_current_partner_code();

		if ( '' !== $current_partner_code && $current_partner_code !== $partner_code ) {
			return;
		}

		if ( '' === $current_partner_code ) {
			$this->set_cookie( self::COOKIE_PARTNER_CODE, $partner_code, $this->resolve_cookie_expiration() );
		}

		$session_key = $this->get_or_create_session_key();
		$visit = $this->visit_repository->get_latest_by_session_key( $session_key );

		if ( is_array( $visit ) && (int) $visit['partner_id'] === (int) $partner['id'] ) {
			$this->visit_repository->update(
				(int) $visit['id'],
				array(
					'last_seen_at' => current_time( 'mysql', true ),
				)
			);

			return;
		}

		$this->visit_repository->create(
			array(
				'partner_id'      => (int) $partner['id'],
				'session_key'     => $session_key,
				'landing_url'     => $this->get_current_url(),
				'referrer_url'    => wp_get_referer() ?: null,
				'utm_source'      => $this->get_query_param( 'utm_source' ),
				'utm_medium'      => $this->get_query_param( 'utm_medium' ),
				'utm_campaign'    => $this->get_query_param( 'utm_campaign' ),
				'ip_hash'         => $this->hash_value( $this->get_server_value( 'REMOTE_ADDR' ) ),
				'user_agent_hash' => $this->hash_value( $this->get_server_value( 'HTTP_USER_AGENT' ) ),
			)
		);
	}

	/**
	 * Get current tracked partner code from cookie.
	 *
	 * @return string
	 */
	public function get_current_partner_code(): string {
		return isset( $_COOKIE[ self::COOKIE_PARTNER_CODE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_PARTNER_CODE ] ) ) : '';
	}

	/**
	 * Get current visitor session key from cookie.
	 *
	 * @return string
	 */
	public function get_current_session_key(): string {
		return isset( $_COOKIE[ self::COOKIE_SESSION_KEY ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_SESSION_KEY ] ) ) : '';
	}

	/**
	 * Ensure visitor has a stable session key.
	 *
	 * @return string
	 */
	private function get_or_create_session_key(): string {
		$session_key = $this->get_current_session_key();

		if ( '' !== $session_key ) {
			return $session_key;
		}

		$session_key = wp_generate_uuid4();
		$this->set_cookie( self::COOKIE_SESSION_KEY, $session_key, time() + ( 10 * YEAR_IN_SECONDS ) );

		return $session_key;
	}

	/**
	 * Resolve cookie expiration timestamp for attribution mode.
	 *
	 * @return int
	 */
	private function resolve_cookie_expiration(): int {
		$mode = $this->settings_service->get_attribution_lifetime_mode();

		if ( RR_Referral_Settings_Service::MODE_DAYS === $mode ) {
			return time() + ( $this->settings_service->get_attribution_lifetime_days() * DAY_IN_SECONDS );
		}

		return time() + ( 10 * YEAR_IN_SECONDS );
	}

	/**
	 * Set cookie and mirror runtime value.
	 *
	 * @param string $name    Cookie name.
	 * @param string $value   Cookie value.
	 * @param int    $expires Expiration timestamp.
	 * @return void
	 */
	private function set_cookie( string $name, string $value, int $expires ): void {
		setcookie( $name, $value, $expires, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE[ $name ] = $value;
	}

	/**
	 * Get current absolute URL.
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		$request_uri = $this->get_server_value( 'REQUEST_URI' );

		return '' !== $request_uri ? home_url( $request_uri ) : home_url( '/' );
	}

	/**
	 * Get sanitized query parameter.
	 *
	 * @param string $key Query key.
	 * @return string
	 */
	private function get_query_param( string $key ): string {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	/**
	 * Get server value as string.
	 *
	 * @param string $key Server key.
	 * @return string
	 */
	private function get_server_value( string $key ): string {
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : '';
	}

	/**
	 * Hash potentially sensitive runtime value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function hash_value( string $value ): string {
		return '' !== $value ? hash( 'sha256', $value ) : '';
	}
}
