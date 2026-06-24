<?php
/**
 * Catalog module bootstrap.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Catalog_Module {

	/**
	 * Property post type registrar.
	 *
	 * @var RR_Property_Post_Type
	 */
	private RR_Property_Post_Type $property_post_type;

	/**
	 * Property location taxonomy registrar.
	 *
	 * @var RR_Property_Location_Taxonomy
	 */
	private RR_Property_Location_Taxonomy $property_location_taxonomy;

	/**
	 * Property type taxonomy registrar.
	 *
	 * @var RR_Property_Type_Taxonomy
	 */
	private RR_Property_Type_Taxonomy $property_type_taxonomy;

	/**
	 * Deal type taxonomy registrar.
	 *
	 * @var RR_Property_Deal_Type_Taxonomy
	 */
	private RR_Property_Deal_Type_Taxonomy $property_deal_type_taxonomy;

	/**
	 * Property feature taxonomy registrar.
	 *
	 * @var RR_Property_Feature_Taxonomy
	 */
	private RR_Property_Feature_Taxonomy $property_feature_taxonomy;

	/**
	 * Location manager service.
	 *
	 * @var RR_Location_Manager
	 */
	private RR_Location_Manager $location_manager;

	/**
	 * Catalog requirements validator.
	 *
	 * @var RR_Catalog_Requirements_Validator
	 */
	private RR_Catalog_Requirements_Validator $requirements_validator;

	/**
	 * Admin location fields controller.
	 *
	 * @var RR_Property_Location_Fields
	 */
	private RR_Property_Location_Fields $property_location_fields;

	/**
	 * Admin classification fields controller.
	 *
	 * @var RR_Property_Classification_Fields
	 */
	private RR_Property_Classification_Fields $property_classification_fields;

	/**
	 * ACF property fields registrar.
	 *
	 * @var RR_Property_ACF_Fields
	 */
	private RR_Property_ACF_Fields $property_acf_fields;

	/**
	 * Property slug manager.
	 *
	 * @var RR_Property_Slug_Manager
	 */
	private RR_Property_Slug_Manager $property_slug_manager;

	/**
	 * Rewrite manager.
	 *
	 * @var RR_Catalog_Rewrite_Manager
	 */
	private RR_Catalog_Rewrite_Manager $rewrite_manager;

	/**
	 * Property downloads manager.
	 *
	 * @var RR_Catalog_Download_Manager
	 */
	private RR_Catalog_Download_Manager $download_manager;

	/**
	 * Property map manager.
	 *
	 * @var RR_Catalog_Map_Manager
	 */
	private RR_Catalog_Map_Manager $map_manager;

	/**
	 * Catalog listing block registrar.
	 *
	 * @var RR_Catalog_Properties_Block
	 */
	private RR_Catalog_Properties_Block $catalog_properties_block;

	/**
	 * Catalog search block registrar.
	 *
	 * @var RR_Catalog_Search_Block
	 */
	private RR_Catalog_Search_Block $catalog_search_block;

	/**
	 * Init module.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_dependencies();

		$this->property_post_type         = new RR_Property_Post_Type();
		$this->property_location_taxonomy = new RR_Property_Location_Taxonomy();
		$this->property_type_taxonomy     = new RR_Property_Type_Taxonomy();
		$this->property_deal_type_taxonomy = new RR_Property_Deal_Type_Taxonomy();
		$this->property_feature_taxonomy   = new RR_Property_Feature_Taxonomy();
		$this->location_manager           = new RR_Location_Manager();
		$this->requirements_validator     = new RR_Catalog_Requirements_Validator();
		$this->property_location_fields   = new RR_Property_Location_Fields( $this->location_manager, $this->requirements_validator );
		$this->property_classification_fields = new RR_Property_Classification_Fields( $this->requirements_validator );
		$this->property_acf_fields        = new RR_Property_ACF_Fields();
		$this->property_slug_manager      = new RR_Property_Slug_Manager();
		$this->rewrite_manager            = new RR_Catalog_Rewrite_Manager();
		$this->download_manager           = new RR_Catalog_Download_Manager();
		$this->map_manager                = new RR_Catalog_Map_Manager();
		$this->catalog_properties_block   = new RR_Catalog_Properties_Block();
		$this->catalog_search_block       = new RR_Catalog_Search_Block();

		$this->property_post_type->init();
		$this->property_location_taxonomy->init();
		$this->property_type_taxonomy->init();
		$this->property_deal_type_taxonomy->init();
		$this->property_feature_taxonomy->init();
		$this->property_location_fields->init();
		$this->property_classification_fields->init();
		$this->property_acf_fields->init();
		$this->property_slug_manager->init();
		$this->rewrite_manager->init();
		$this->download_manager->init();
		$this->map_manager->init();
		$this->catalog_properties_block->init();
		$this->catalog_search_block->init();
	}

	/**
	 * Load module files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once __DIR__ . '/helpers.php';
		require_once __DIR__ . '/post-types/class-property-post-type.php';
		require_once __DIR__ . '/taxonomies/class-property-location-taxonomy.php';
		require_once __DIR__ . '/taxonomies/class-property-type-taxonomy.php';
		require_once __DIR__ . '/taxonomies/class-property-deal-type-taxonomy.php';
		require_once __DIR__ . '/taxonomies/class-property-feature-taxonomy.php';
		require_once __DIR__ . '/services/class-location-manager.php';
		require_once __DIR__ . '/services/class-requirements-validator.php';
		require_once __DIR__ . '/services/class-property-slug-manager.php';
		require_once __DIR__ . '/services/class-rewrite-manager.php';
		require_once __DIR__ . '/services/class-download-manager.php';
		require_once __DIR__ . '/services/class-map-manager.php';
		require_once __DIR__ . '/admin/class-property-acf-fields.php';
		require_once __DIR__ . '/admin/class-property-location-fields.php';
		require_once __DIR__ . '/admin/class-property-classification-fields.php';
		require_once __DIR__ . '/blocks/class-catalog-properties-block.php';
		require_once __DIR__ . '/blocks/class-catalog-search-block.php';
	}
}
