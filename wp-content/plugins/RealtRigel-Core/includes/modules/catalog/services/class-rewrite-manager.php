<?php
/**
 * Catalog rewrite maintenance.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Rewrite_Manager {

	/**
	 * Rewrite version option key.
	 */
	private const OPTION_KEY = 'rr_catalog_rewrite_version';

	/**
	 * Current rewrite version.
	 */
	private const VERSION = '2026-03-31-property-features-taxonomy';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );
	}

	/**
	 * Flush rewrite rules once after rewrite changes.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( self::OPTION_KEY ) === self::VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::OPTION_KEY, self::VERSION, false );
	}
}
