<?php
/**
 * Content architecture module bootstrap.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Content_Module {

	/**
	 * Hub post type registrar.
	 *
	 * @var RR_Hub_Post_Type
	 */
	private RR_Hub_Post_Type $hub_post_type;

	/**
	 * Article post type registrar.
	 *
	 * @var RR_Article_Post_Type
	 */
	private RR_Article_Post_Type $article_post_type;

	/**
	 * Service post type registrar.
	 *
	 * @var RR_Service_Post_Type
	 */
	private RR_Service_Post_Type $service_post_type;

	/**
	 * ACF fields registrar.
	 *
	 * @var RR_Content_ACF_Fields
	 */
	private RR_Content_ACF_Fields $acf_fields;

	/**
	 * Rewrite manager.
	 *
	 * @var RR_Content_Rewrite_Manager
	 */
	private RR_Content_Rewrite_Manager $rewrite_manager;

	/**
	 * Init module.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_dependencies();

		$this->hub_post_type     = new RR_Hub_Post_Type();
		$this->article_post_type = new RR_Article_Post_Type();
		$this->service_post_type = new RR_Service_Post_Type();
		$this->acf_fields        = new RR_Content_ACF_Fields();
		$this->rewrite_manager  = new RR_Content_Rewrite_Manager();

		$this->hub_post_type->init();
		$this->article_post_type->init();
		$this->service_post_type->init();
		$this->acf_fields->init();
		$this->rewrite_manager->init();
	}

	/**
	 * Load module files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/post-types/class-hub-post-type.php';
		require_once __DIR__ . '/post-types/class-article-post-type.php';
		require_once __DIR__ . '/post-types/class-service-post-type.php';
		require_once __DIR__ . '/admin/class-content-acf-fields.php';
		require_once __DIR__ . '/services/class-content-rewrite-manager.php';
	}
}
