<?php
/**
 * Referral database schema provider.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Schema {

	/**
	 * Current schema version.
	 */
	public const VERSION = '1.1.2';

	/**
	 * Get all dbDelta SQL statements.
	 *
	 * @return string[]
	 */
	public function get_table_definitions(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$partners_table = rr_referral_table_name( 'partners' );
		$visits_table   = rr_referral_table_name( 'visits' );
		$leads_table    = rr_referral_table_name( 'leads' );
		$history_table  = rr_referral_table_name( 'lead_status_history' );

		return array(
			"CREATE TABLE {$partners_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				partner_code varchar(64) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'active',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY partner_code (partner_code),
				UNIQUE KEY user_id (user_id),
				KEY status (status)
			) {$charset_collate};",
			"CREATE TABLE {$visits_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				partner_id bigint(20) unsigned NOT NULL,
				session_key varchar(128) NOT NULL,
				landing_url text NULL,
				referrer_url text NULL,
				utm_source varchar(100) NOT NULL DEFAULT '',
				utm_medium varchar(100) NOT NULL DEFAULT '',
				utm_campaign varchar(100) NOT NULL DEFAULT '',
				ip_hash char(64) NOT NULL DEFAULT '',
				user_agent_hash char(64) NOT NULL DEFAULT '',
				first_seen_at datetime NOT NULL,
				last_seen_at datetime NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY partner_id (partner_id),
				KEY session_key (session_key),
				KEY first_seen_at (first_seen_at)
			) {$charset_collate};",
			"CREATE TABLE {$leads_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				partner_id bigint(20) unsigned NOT NULL,
				visit_id bigint(20) unsigned NULL,
				source_type varchar(32) NOT NULL,
				contact_name varchar(191) NOT NULL DEFAULT '',
				contact_phone varchar(191) NOT NULL DEFAULT '',
				contact_email varchar(191) NOT NULL DEFAULT '',
				contact_telegram varchar(191) NOT NULL DEFAULT '',
				contact_message text NULL,
				catalog_post_id bigint(20) unsigned NULL,
				object_title varchar(255) NOT NULL DEFAULT '',
				object_url text NULL,
				redirect_url text NULL,
				language varchar(20) NOT NULL DEFAULT '',
				user_agent text NULL,
				status varchar(32) NOT NULL DEFAULT 'new',
				bitrix_lead_id varchar(64) NOT NULL DEFAULT '',
				bitrix_status_id varchar(64) NOT NULL DEFAULT '',
				bitrix_status_label varchar(191) NOT NULL DEFAULT '',
				bitrix_synced_at datetime NULL,
				attribution_model varchar(32) NOT NULL DEFAULT 'first_click',
				attribution_expires_at datetime NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY partner_id (partner_id),
				KEY visit_id (visit_id),
				KEY source_type (source_type),
				KEY status (status),
				KEY catalog_post_id (catalog_post_id)
			) {$charset_collate};",
			"CREATE TABLE {$history_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				lead_id bigint(20) unsigned NOT NULL,
				old_status varchar(32) NOT NULL DEFAULT '',
				new_status varchar(32) NOT NULL,
				source varchar(20) NOT NULL DEFAULT 'site',
				comment text NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY lead_id (lead_id),
				KEY new_status (new_status),
				KEY source (source)
			) {$charset_collate};",
		);
	}
}
