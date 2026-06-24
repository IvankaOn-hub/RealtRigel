<?php
/**
 * Referral role service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Role_Service {

	/**
	 * Partner role slug.
	 */
	public const ROLE = 'partner';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_role' ), 1 );
		add_action( 'admin_init', array( $this, 'restrict_admin_access' ) );
		add_filter( 'show_admin_bar', array( $this, 'maybe_hide_admin_bar' ) );
	}

	/**
	 * Ensure partner role exists.
	 *
	 * @return void
	 */
	public function register_role(): void {
		if ( get_role( self::ROLE ) ) {
			return;
		}

		add_role(
			self::ROLE,
			__( 'Partner', 'realtrigel-core' ),
			array(
				'read' => true,
			)
		);
	}

	/**
	 * Redirect partners away from wp-admin while keeping AJAX available.
	 *
	 * @return void
	 */
	public function restrict_admin_access(): void {
		if ( ! $this->is_partner_user() || wp_doing_ajax() ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/**
	 * Hide admin bar for partners on the frontend.
	 *
	 * @param bool $show Whether admin bar should be displayed.
	 * @return bool
	 */
	public function maybe_hide_admin_bar( bool $show ): bool {
		if ( $this->is_partner_user() ) {
			return false;
		}

		return $show;
	}

	/**
	 * Determine whether current user has the partner role.
	 *
	 * @return bool
	 */
	private function is_partner_user(): bool {
		$user = wp_get_current_user();

		if ( ! $user instanceof WP_User || 0 === (int) $user->ID ) {
			return false;
		}

		return in_array( self::ROLE, (array) $user->roles, true );
	}
}
