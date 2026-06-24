<?php
/**
 * Referral database installer.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Installer {

	/**
	 * Schema version option key.
	 */
	private const OPTION_KEY = 'rr_referral_schema_version';

	/**
	 * Schema provider.
	 *
	 * @var RR_Referral_Schema
	 */
	private RR_Referral_Schema $schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->schema = new RR_Referral_Schema();
	}

	/**
	 * Register installer hook.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'maybe_install' ), 5 );
	}

	/**
	 * Install or update schema when needed.
	 *
	 * @return void
	 */
	public function maybe_install(): void {
		$current_version = get_option( self::OPTION_KEY, '' );

		if ( RR_Referral_Schema::VERSION === $current_version ) {
			return;
		}

		$this->install();
	}

	/**
	 * Run dbDelta for all referral tables.
	 *
	 * @return void
	 */
	private function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $this->schema->get_table_definitions() as $sql ) {
			dbDelta( $sql );
		}

		update_option( self::OPTION_KEY, RR_Referral_Schema::VERSION, false );
	}
}
