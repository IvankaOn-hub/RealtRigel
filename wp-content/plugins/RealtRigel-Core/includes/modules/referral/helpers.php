<?php
/**
 * Referral module helpers.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get referral table name with current WordPress prefix.
 *
 * @param string $suffix Table suffix without prefix.
 * @return string
 */
function rr_referral_table_name( string $suffix ): string {
	global $wpdb;

	return $wpdb->prefix . 'rr_referral_' . $suffix;
}
