<?php
/**
 * Referral partner service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Partner_Service {

	/**
	 * Consent accepted user meta key.
	 */
	public const META_CONSENT_ACCEPTED = 'rr_referral_consent_accepted';

	/**
	 * Consent accepted at user meta key.
	 */
	public const META_CONSENT_ACCEPTED_AT = 'rr_referral_consent_accepted_at';

	/**
	 * Partner phone user meta key.
	 */
	public const META_PHONE = 'rr_referral_partner_phone';

	/**
	 * Partner repository.
	 *
	 * @var RR_Referral_Partner_Repository
	 */
	private RR_Referral_Partner_Repository $partner_repository;

	/**
	 * Link service.
	 *
	 * @var RR_Referral_Link_Service
	 */
	private RR_Referral_Link_Service $link_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Repository $partner_repository Partner repository.
	 * @param RR_Referral_Link_Service       $link_service Link service.
	 */
	public function __construct( RR_Referral_Partner_Repository $partner_repository, RR_Referral_Link_Service $link_service ) {
		$this->partner_repository = $partner_repository;
		$this->link_service       = $link_service;
	}

	/**
	 * Register a new partner account.
	 *
	 * @param array<string, mixed> $data Registration data.
	 * @return int|\WP_Error
	 */
	public function register_partner_account( array $data ) {
		$email    = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';
		$password = isset( $data['password'] ) ? (string) $data['password'] : '';
		$name     = isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
		$phone    = isset( $data['phone'] ) ? sanitize_text_field( (string) $data['phone'] ) : '';
		$consent  = ! empty( $data['consent'] );

		if ( '' === $name ) {
			return new \WP_Error( 'invalid_name', __( 'Enter your name.', 'realtrigel-core' ) );
		}

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Enter a valid email address.', 'realtrigel-core' ) );
		}

		if ( email_exists( $email ) ) {
			return new \WP_Error( 'email_exists', __( 'An account with this email already exists.', 'realtrigel-core' ) );
		}

		if ( strlen( $password ) < 8 ) {
			return new \WP_Error( 'invalid_password', __( 'Password must be at least 8 characters long.', 'realtrigel-core' ) );
		}

		if ( ! $consent ) {
			return new \WP_Error( 'missing_consent', __( 'You must accept the terms to register.', 'realtrigel-core' ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $email,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => RR_Referral_Role_Service::ROLE,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, self::META_CONSENT_ACCEPTED, 1 );
		update_user_meta( $user_id, self::META_CONSENT_ACCEPTED_AT, current_time( 'mysql', true ) );

		if ( '' !== $phone ) {
			update_user_meta( $user_id, self::META_PHONE, $phone );
		}

		$partner = $this->create_partner_for_user( (int) $user_id );

		if ( ! is_array( $partner ) || empty( $partner['id'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( (int) $user_id );

			return new \WP_Error( 'partner_creation_failed', __( 'Partner profile could not be created. Please try again.', 'realtrigel-core' ) );
		}

		return (int) $user_id;
	}

	/**
	 * Ensure partner row exists for user.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>|null
	 */
	public function create_partner_for_user( int $user_id ): ?array {
		$existing = $this->partner_repository->get_by_user_id( $user_id );

		if ( is_array( $existing ) ) {
			return $existing;
		}

		$partner_code = $this->generate_unique_partner_code();
		$partner_id   = $this->partner_repository->create(
			array(
				'user_id'      => $user_id,
				'partner_code' => $partner_code,
				'status'       => 'active',
			)
		);

		return $partner_id > 0 ? $this->partner_repository->get_by_id( $partner_id ) : null;
	}

	/**
	 * Build default referral URL for user.
	 *
	 * @param int $user_id User id.
	 * @return string
	 */
	public function get_referral_url_for_user( int $user_id ): string {
		$partner = $this->partner_repository->get_by_user_id( $user_id );

		if ( ! is_array( $partner ) || empty( $partner['partner_code'] ) ) {
			return '';
		}

		return $this->link_service->build_referral_url( (string) $partner['partner_code'] );
	}

	/**
	 * Get partner row for user.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>|null
	 */
	public function get_partner_by_user_id( int $user_id ): ?array {
		$partner = $this->partner_repository->get_by_user_id( $user_id );

		return is_array( $partner ) ? $partner : null;
	}

	/**
	 * Generate unique partner code.
	 *
	 * @return string
	 */
	private function generate_unique_partner_code(): string {
		for ( $attempt = 0; $attempt < 10; $attempt++ ) {
			$partner_code = $this->link_service->generate_partner_code();

			if ( ! $this->partner_repository->get_by_partner_code( $partner_code ) ) {
				return $partner_code;
			}
		}

		return strtolower( wp_generate_password( 16, false, false ) );
	}
}
