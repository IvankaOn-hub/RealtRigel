<?php
/**
 * Plugin Name: RealtRigel Core
 * Description: Modular core plugin for project features
 * Version: 1.0.0
 * Requires PHP: 8.0
 * Text Domain: realtrigel-core
 * Domain Path: /languages
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'RR_CORE_PLUGIN_FILE' ) ) {
	define( 'RR_CORE_PLUGIN_FILE', __FILE__ );
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/includes/core/class-plugin.php';

/**
 * Load plugin translations.
 *
 * @return void
 */
function rr_core_load_textdomain(): void {
	load_plugin_textdomain( 'realtrigel-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Boot plugin.
 *
 * @return RR_Core_Plugin
 */
function rr_core_plugin(): RR_Core_Plugin {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new RR_Core_Plugin();
		$plugin->init();
	}

	return $plugin;
}

add_action( 'plugins_loaded', 'rr_core_plugin' );
add_action( 'plugins_loaded', 'rr_core_load_textdomain' );
