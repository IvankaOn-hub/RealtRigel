<?php
/**
 * Referral link service.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Link_Service {

	/**
	 * Referral query parameter name.
	 */
	public const QUERY_VAR = 'ref';

	/**
	 * Build partner referral URL.
	 *
	 * @param string      $partner_code Partner code.
	 * @param string|null $path         Optional relative path.
	 * @return string
	 */
	public function build_referral_url( string $partner_code, ?string $path = null ): string {
		$base_url = null !== $path ? home_url( ltrim( $path, '/' ) ) : home_url( '/' );

		return add_query_arg( self::QUERY_VAR, rawurlencode( $partner_code ), $base_url );
	}

	/**
	 * Generate partner code.
	 *
	 * @return string
	 */
	public function generate_partner_code(): string {
		return strtolower( wp_generate_password( 12, false, false ) );
	}
}
