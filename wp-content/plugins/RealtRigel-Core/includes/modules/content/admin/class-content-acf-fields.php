<?php
/**
 * Content ACF fields registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Content_ACF_Fields {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'acf/init', array( $this, 'register_field_groups' ) );
	}

	/**
	 * Register local ACF field groups.
	 *
	 * @return void
	 */
	public function register_field_groups(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$this->register_article_fields();
		$this->register_hub_fields();
		$this->register_service_fields();
	}

	/**
	 * Register article fields.
	 *
	 * @return void
	 */
	private function register_article_fields(): void {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_article_structure',
				'title'                 => __( 'SEO-структура статьи', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_article_primary_hub',
						'label'         => __( 'Основной хаб', 'realtrigel-core' ),
						'name'          => 'primary_hub',
						'type'          => 'post_object',
						'instructions'  => __( 'Выберите хаб, от которого строится URL и breadcrumbs статьи.', 'realtrigel-core' ),
						'required'      => 1,
						'post_type'     => array( RR_Hub_Post_Type::POST_TYPE ),
						'return_format' => 'id',
						'ui'            => 1,
						'wrapper'       => array(
							'width' => '50',
						),
					),
					array(
						'key'           => 'field_rr_article_primary_subhub',
						'label'         => __( 'Подхаб', 'realtrigel-core' ),
						'name'          => 'primary_subhub',
						'type'          => 'post_object',
						'instructions'  => __( 'Необязательно. Если выбран, URL статьи строится через подхаб.', 'realtrigel-core' ),
						'required'      => 0,
						'post_type'     => array( RR_Hub_Post_Type::POST_TYPE ),
						'return_format' => 'id',
						'ui'            => 1,
						'allow_null'    => 1,
						'wrapper'       => array(
							'width' => '50',
						),
					),
					array(
						'key'           => 'field_rr_article_layout',
						'label'         => __( 'Шаблон статьи', 'realtrigel-core' ),
						'name'          => 'article_layout',
						'type'          => 'select',
						'instructions'  => __( 'Выберите визуальный режим статьи.', 'realtrigel-core' ),
						'required'      => 0,
						'choices'       => array(
							'full'    => __( 'Без сайдбара', 'realtrigel-core' ),
							'sidebar' => __( 'С сайдбаром', 'realtrigel-core' ),
						),
						'default_value' => 'sidebar',
						'return_format' => 'value',
						'wrapper'       => array(
							'width' => '50',
						),
					),
					array(
						'key'           => 'field_rr_article_toc_enabled',
						'label'         => __( 'Оглавление', 'realtrigel-core' ),
						'name'          => 'toc_enabled',
						'type'          => 'true_false',
						'instructions'  => __( 'Показывать автоматическое оглавление для этой статьи.', 'realtrigel-core' ),
						'required'      => 0,
						'default_value' => 1,
						'ui'            => 1,
						'wrapper'       => array(
							'width' => '50',
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Article_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
				'show_in_rest'          => 1,
			)
		);
	}

	/**
	 * Register hub fields.
	 *
	 * @return void
	 */
	private function register_hub_fields(): void {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_hub_settings',
				'title'                 => __( 'Настройки хаба', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_hub_display_mode',
						'label'         => __( 'Тип страницы', 'realtrigel-core' ),
						'name'          => 'hub_display_mode',
						'type'          => 'select',
						'required'      => 0,
						'choices'       => array(
							'hub'    => __( 'Хаб', 'realtrigel-core' ),
							'subhub' => __( 'Подхаб', 'realtrigel-core' ),
						),
						'default_value' => 'hub',
						'return_format' => 'value',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Hub_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'side',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
				'show_in_rest'          => 1,
			)
		);
	}

	/**
	 * Register service fields.
	 *
	 * @return void
	 */
	private function register_service_fields(): void {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_service_settings',
				'title'                 => __( 'Настройки услуги', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_service_primary_cta',
						'label'         => __( 'Основной CTA', 'realtrigel-core' ),
						'name'          => 'primary_cta',
						'type'          => 'text',
						'required'      => 0,
						'default_value' => __( 'Получить консультацию', 'realtrigel-core' ),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Service_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'side',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
				'show_in_rest'          => 1,
			)
		);
	}
}
