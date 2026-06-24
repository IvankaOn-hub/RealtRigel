<?php
/**
 * Property ACF fields registration.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_ACF_Fields {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'acf/init', array( $this, 'register_field_group' ) );
		add_filter( 'acf/prepare_field/key=field_rr_catalog_gallery', array( $this, 'hide_legacy_gallery_field' ) );
	}

	/**
	 * Hide legacy image-only gallery field in property editor.
	 *
	 * The field stays registered so existing data and frontend fallbacks keep working.
	 *
	 * @param array<string, mixed> $field ACF field config.
	 * @return array<string, mixed>|false
	 */
	public function hide_legacy_gallery_field( array $field ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || RR_Property_Post_Type::POST_TYPE !== $screen->post_type ) {
			return $field;
		}

		return false;
	}

	/**
	 * Register local ACF field groups for property object.
	 *
	 * @return void
	 */
	public function register_field_group(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_catalog_property_metrics',
				'title'                 => __( 'Параметры объекта', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_catalog_price',
						'label'         => __( 'Цена', 'realtrigel-core' ),
						'name'          => 'price',
						'type'          => 'number',
						'instructions'  => __( 'Введите стоимость объекта.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '34',
						),
						'default_value' => '',
						'min'           => 0,
						'step'          => 1,
					),
					array(
						'key'           => 'field_rr_catalog_currency',
						'label'         => __( 'Валюта', 'realtrigel-core' ),
						'name'          => 'currency',
						'type'          => 'select',
						'instructions'  => __( 'Выберите валюту цены.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '16',
						),
						'choices'       => array(
							'PLN' => 'PLN',
							'USD' => 'USD',
							'EUR' => 'EUR',
						),
						'default_value' => 'USD',
						'allow_null'    => 0,
						'multiple'      => 0,
						'ui'            => 0,
						'return_format' => 'value',
					),
					array(
						'key'           => 'field_rr_catalog_area',
						'label'         => __( 'Площадь', 'realtrigel-core' ),
						'name'          => 'area',
						'type'          => 'number',
						'instructions'  => __( 'Укажите площадь объекта.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '25',
						),
						'default_value' => '',
						'min'           => 0,
						'step'          => 0.01,
						'append'        => __( 'м²', 'realtrigel-core' ),
					),
					array(
						'key'           => 'field_rr_catalog_floor',
						'label'         => __( 'Этаж', 'realtrigel-core' ),
						'name'          => 'floor',
						'type'          => 'number',
						'instructions'  => __( 'Укажите этаж объекта. Поле необязательное.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '25',
						),
						'default_value' => '',
						'step'          => 1,
					),
					array(
						'key'           => 'field_rr_catalog_calculate_price_per_meter',
						'label'         => __( 'Пересчитывать цену за м²', 'realtrigel-core' ),
						'name'          => 'calculate_price_per_meter',
						'type'          => 'true_false',
						'instructions'  => __( 'Если включено, цена за квадратный метр рассчитывается автоматически из цены и площади.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '50',
						),
						'default_value' => 1,
						'ui'            => 1,
						'ui_on_text'    => __( 'Да', 'realtrigel-core' ),
						'ui_off_text'   => __( 'Нет', 'realtrigel-core' ),
					),
					array(
						'key'               => 'field_rr_catalog_parameter_pairs',
						'label'             => __( 'Дополнительные параметры', 'realtrigel-core' ),
						'name'              => 'parameter_pairs',
						'type'              => 'repeater',
						'instructions'      => __( 'Добавьте пары заголовок / значение для параметров объекта.', 'realtrigel-core' ),
						'required'          => 0,
						'layout'            => 'row',
						'button_label'      => __( 'Добавить параметр', 'realtrigel-core' ),
						'collapsed'         => 'field_rr_catalog_parameter_pair_label',
						'min'               => 0,
						'wrapper'           => array(
							'width' => '100',
						),
						'sub_fields'        => array(
							array(
								'key'           => 'field_rr_catalog_parameter_pair_label',
								'label'         => __( 'Заголовок', 'realtrigel-core' ),
								'name'          => 'label',
								'type'          => 'text',
								'instructions'  => __( 'Название параметра.', 'realtrigel-core' ),
								'required'      => 1,
								'wrapper'       => array(
									'width' => '40',
								),
								'default_value' => '',
							),
							array(
								'key'           => 'field_rr_catalog_parameter_pair_value',
								'label'         => __( 'Значение', 'realtrigel-core' ),
								'name'          => 'value',
								'type'          => 'text',
								'instructions'  => __( 'Значение параметра.', 'realtrigel-core' ),
								'required'      => 1,
								'wrapper'       => array(
									'width' => '60',
								),
								'default_value' => '',
							),
						),
					),
					array(
						'key'           => 'field_rr_catalog_map_address',
						'label'         => __( 'Адрес для карты', 'realtrigel-core' ),
						'name'          => 'map_address',
						'type'          => 'text',
						'instructions'  => __( 'Используется для геокодинга через Nominatim, если координаты еще не заполнены.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '100',
						),
						'default_value' => '',
					),
					array(
						'key'           => 'field_rr_catalog_map_lat',
						'label'         => __( 'Широта', 'realtrigel-core' ),
						'name'          => 'map_lat',
						'type'          => 'number',
						'instructions'  => __( 'Если заполнена вместе с долготой, геокодинг не выполняется.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '34',
						),
						'default_value' => '',
						'step'          => 0.000001,
					),
					array(
						'key'           => 'field_rr_catalog_map_lng',
						'label'         => __( 'Долгота', 'realtrigel-core' ),
						'name'          => 'map_lng',
						'type'          => 'number',
						'instructions'  => __( 'Если очищена, координаты будут пересчитаны при следующем сохранении по адресу.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '33',
						),
						'default_value' => '',
						'step'          => 0.000001,
					),
					array(
						'key'           => 'field_rr_catalog_map_show_exact_location',
						'label'         => __( 'Показывать точное расположение', 'realtrigel-core' ),
						'name'          => 'map_show_exact_location',
						'type'          => 'true_false',
						'instructions'  => __( 'Если выключено, на фронте будут использоваться размытые координаты без маркера и без адреса.', 'realtrigel-core' ),
						'required'      => 0,
						'wrapper'       => array(
							'width' => '33',
						),
						'default_value' => 0,
						'ui'            => 1,
					),
					array(
						'key'           => 'field_rr_catalog_gallery',
						'label'         => __( 'Галерея', 'realtrigel-core' ),
						'name'          => 'gallery',
						'type'          => 'gallery',
						'instructions'  => __( 'Добавьте одно или несколько фото объекта.', 'realtrigel-core' ),
						'required'      => 0,
						'min'           => 1,
						'insert'        => 'append',
						'library'       => 'all',
						'preview_size'  => 'medium',
						'return_format' => 'array',
						'wrapper'       => array(
							'width' => '100',
						),
					),
					array(
						'key'               => 'field_rr_catalog_media_gallery',
						'label'             => __( 'Медиа-галерея', 'realtrigel-core' ),
						'name'              => 'media_gallery',
						'type'              => 'repeater',
						'instructions'      => __( 'Добавьте изображения и видео для галереи объекта. Это поле поддерживает смешанный набор медиа.', 'realtrigel-core' ),
						'required'          => 0,
						'layout'            => 'row',
						'button_label'      => __( 'Добавить медиа', 'realtrigel-core' ),
						'collapsed'         => 'field_rr_catalog_media_gallery_file',
						'min'               => 0,
						'wrapper'           => array(
							'width' => '100',
						),
						'sub_fields'        => array(
							array(
								'key'           => 'field_rr_catalog_media_gallery_file',
								'label'         => __( 'Файл', 'realtrigel-core' ),
								'name'          => 'media',
								'type'          => 'file',
								'instructions'  => __( 'Поддерживаются изображения и видео из медиабиблиотеки.', 'realtrigel-core' ),
								'required'      => 1,
								'return_format' => 'array',
								'library'       => 'all',
								'mime_types'    => 'jpg,jpeg,png,gif,webp,svg,mp4,webm,ogg,mov,m4v',
								'wrapper'       => array(
									'width' => '100',
								),
							),
						),
					),
					array(
						'key'               => 'field_rr_catalog_downloads',
						'label'             => __( 'Файлы', 'realtrigel-core' ),
						'name'              => 'downloads',
						'type'              => 'repeater',
						'instructions'      => __( 'Добавьте файлы, доступные для скачивания.', 'realtrigel-core' ),
						'required'          => 0,
						'layout'            => 'row',
						'button_label'      => __( 'Добавить файл', 'realtrigel-core' ),
						'collapsed'         => 'field_rr_catalog_download_title',
						'min'               => 0,
						'wrapper'           => array(
							'width' => '100',
						),
						'sub_fields'        => array(
							array(
								'key'           => 'field_rr_catalog_download_title',
								'label'         => __( 'Название', 'realtrigel-core' ),
								'name'          => 'title',
								'type'          => 'text',
								'instructions'  => __( 'Необязательно. Если пусто, будет использовано имя файла.', 'realtrigel-core' ),
								'required'      => 0,
								'wrapper'       => array(
									'width' => '40',
								),
								'default_value' => '',
							),
							array(
								'key'           => 'field_rr_catalog_download_file',
								'label'         => __( 'Файл', 'realtrigel-core' ),
								'name'          => 'file',
								'type'          => 'file',
								'instructions'  => __( 'Выберите файл для скачивания.', 'realtrigel-core' ),
								'required'      => 1,
								'return_format' => 'array',
								'library'       => 'all',
								'wrapper'       => array(
									'width' => '60',
								),
							),
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Property_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
				'show_in_rest'          => 1,
			)
		);

		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_catalog_property_features',
				'title'                 => __( 'Дополнительные характеристики объекта', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_catalog_features',
						'label'         => __( 'Дополнительные характеристики объекта', 'realtrigel-core' ),
						'name'          => 'features',
						'type'          => 'taxonomy',
						'instructions'  => __( 'Выберите одну или несколько характеристик объекта.', 'realtrigel-core' ),
						'required'      => 0,
						'taxonomy'      => RR_Property_Feature_Taxonomy::TAXONOMY,
						'field_type'    => 'multi_select',
						'allow_null'    => 1,
						'add_term'      => 0,
						'save_terms'    => 1,
						'load_terms'    => 1,
						'return_format' => 'id',
						'multiple'      => 1,
						'bidirectional' => 0,
						'wrapper'       => array(
							'width' => '100',
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Property_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 1,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
				'show_in_rest'          => 1,
			)
		);

		acf_add_local_field_group(
			array(
				'key'                   => 'group_rr_catalog_property_social',
				'title'                 => __( 'Социальные сети', 'realtrigel-core' ),
				'fields'                => array(
					array(
						'key'           => 'field_rr_catalog_social_description',
						'label'         => __( 'Описание для социальных сетей', 'realtrigel-core' ),
						'name'          => 'social_description',
						'type'          => 'wysiwyg',
						'instructions'  => __( 'Этот текст будет использоваться для шаринга объекта. Если поле пустое, можно использовать обычное описание объекта как fallback.', 'realtrigel-core' ),
						'required'      => 0,
						'default_value' => '',
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'delay'         => 0,
						'wrapper'       => array(
							'width' => '100',
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => RR_Property_Post_Type::POST_TYPE,
						),
					),
				),
				'menu_order'            => 2,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
				'show_in_rest'          => 1,
			)
		);
	}
}
