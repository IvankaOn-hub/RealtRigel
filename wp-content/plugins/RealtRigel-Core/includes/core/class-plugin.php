<?php
/**
 * Main plugin bootstrap class.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Core_Plugin {

	/**
	 * Global site slug manager.
	 *
	 * @var RR_Site_Slug_Manager|null
	 */
	private ?RR_Site_Slug_Manager $site_slug_manager = null;

	/**
	 * Catalog module instance.
	 *
	 * @var RR_Catalog_Module|null
	 */
	private ?RR_Catalog_Module $catalog_module = null;

	/**
	 * Content module instance.
	 *
	 * @var RR_Content_Module|null
	 */
	private ?RR_Content_Module $content_module = null;

	/**
	 * Referral module instance.
	 *
	 * @var RR_Referral_Module|null
	 */
	private ?RR_Referral_Module $referral_module = null;

	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_dependencies();

		$this->site_slug_manager = new RR_Site_Slug_Manager();
		$this->catalog_module = new RR_Catalog_Module();
		$this->content_module = new RR_Content_Module();
		$this->referral_module = new RR_Referral_Module();

		$this->site_slug_manager->init();
		$this->catalog_module->init();
		$this->content_module->init();
		$this->referral_module->init();
	}

	/**
	 * Load required classes.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/services/class-site-slug-manager.php';
		require_once __DIR__ . '/../modules/catalog/class-catalog-module.php';
		require_once __DIR__ . '/../modules/content/class-content-module.php';
		require_once __DIR__ . '/../modules/referral/class-referral-module.php';
	}
}
