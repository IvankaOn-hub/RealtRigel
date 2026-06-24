<?php
/**
 * Theme settings page.
 *
 * @package RealtRigel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get supported icon fields for theme settings.
 *
 * @return array<string, string>
 */
function realtrigel_get_theme_icon_fields(): array {
	return array(
		'search'   => __( 'Иконка поиска', 'realtrigel' ),
		'area'     => __( 'Иконка площади', 'realtrigel' ),
		'floor'    => __( 'Иконка этажа', 'realtrigel' ),
		'location' => __( 'Иконка геолокации', 'realtrigel' ),
	);
}

/**
 * Register theme settings page.
 *
 * @return void
 */
function realtrigel_register_theme_settings_page(): void {
	add_theme_page(
		__( 'Настройки темы', 'realtrigel' ),
		__( 'Настройки темы', 'realtrigel' ),
		'manage_options',
		'realtrigel-theme-settings',
		'realtrigel_render_theme_settings_page'
	);
}
add_action( 'admin_menu', 'realtrigel_register_theme_settings_page' );

/**
 * Register theme settings.
 *
 * @return void
 */
function realtrigel_register_theme_settings(): void {
	register_setting(
		'realtrigel_theme_settings',
		'realtrigel_theme_icons',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'realtrigel_sanitize_theme_icons',
			'default'           => array(),
		)
	);

	add_settings_section(
		'realtrigel_theme_icons_section',
		__( 'Иконки темы', 'realtrigel' ),
		'realtrigel_render_theme_icons_section',
		'realtrigel-theme-settings'
	);

	foreach ( realtrigel_get_theme_icon_fields() as $key => $label ) {
		add_settings_field(
			'realtrigel_theme_icon_' . $key,
			$label,
			'realtrigel_render_theme_icon_field',
			'realtrigel-theme-settings',
			'realtrigel_theme_icons_section',
			array(
				'key'   => $key,
				'label' => $label,
			)
		);
	}

}
add_action( 'admin_init', 'realtrigel_register_theme_settings' );

/**
 * Enqueue admin assets for theme settings page.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function realtrigel_enqueue_theme_settings_assets( string $hook_suffix ): void {
	if ( 'appearance_page_realtrigel-theme-settings' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'realtrigel-theme-settings',
		get_template_directory_uri() . '/assets/js/admin-theme-settings.js',
		array( 'jquery' ),
		(string) filemtime( get_template_directory() . '/assets/js/admin-theme-settings.js' ),
		true
	);

	wp_localize_script(
		'realtrigel-theme-settings',
		'RRTThemeSettings',
		array(
			'title'       => __( 'Выберите иконку', 'realtrigel' ),
			'buttonLabel' => __( 'Использовать иконку', 'realtrigel' ),
			'removeLabel' => __( 'Удалить', 'realtrigel' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'realtrigel_enqueue_theme_settings_assets' );

/**
 * Sanitize uploaded icon option values.
 *
 * @param mixed $value Raw option value.
 * @return array<string, int>
 */
function realtrigel_sanitize_theme_icons( $value ): array {
	$sanitized = array();
	$value     = is_array( $value ) ? $value : array();

	foreach ( realtrigel_get_theme_icon_fields() as $key => $label ) {
		$sanitized[ $key ] = isset( $value[ $key ] ) ? max( 0, (int) $value[ $key ] ) : 0;
	}

	return $sanitized;
}

/**
 * Render section description.
 *
 * @return void
 */
function realtrigel_render_theme_icons_section(): void {
	echo '<p>' . esc_html__( 'Загрузите иконки, которые будут использоваться в интерфейсе темы.', 'realtrigel' ) . '</p>';
}

/**
 * Render single icon upload field.
 *
 * @param array<string, string> $args Field args.
 * @return void
 */
function realtrigel_render_theme_icon_field( array $args ): void {
	$key      = isset( $args['key'] ) ? sanitize_key( $args['key'] ) : '';
	$values   = get_option( 'realtrigel_theme_icons', array() );
	$value    = isset( $values[ $key ] ) ? (int) $values[ $key ] : 0;
	$image_url = $value > 0 ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';
	$field_id = 'realtrigel_theme_icon_' . $key;
	?>
	<div class="realtrigel-theme-icon-field" data-theme-icon-field>
		<input
			type="hidden"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="realtrigel_theme_icons[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( (string) $value ); ?>"
			data-theme-icon-input
		/>

		<div class="realtrigel-theme-icon-field__preview" style="margin-bottom:12px;">
			<?php if ( '' !== $image_url ) : ?>
				<img
					src="<?php echo esc_url( $image_url ); ?>"
					alt=""
					style="max-width:64px;max-height:64px;display:block;border:1px solid #dcdcde;border-radius:8px;padding:6px;background:#fff;"
					data-theme-icon-preview
				/>
			<?php else : ?>
				<img
					src=""
					alt=""
					style="max-width:64px;max-height:64px;display:none;border:1px solid #dcdcde;border-radius:8px;padding:6px;background:#fff;"
					data-theme-icon-preview
				/>
			<?php endif; ?>
		</div>

		<button type="button" class="button button-secondary" data-theme-icon-upload>
			<?php esc_html_e( 'Загрузить иконку', 'realtrigel' ); ?>
		</button>

		<button type="button" class="button-link-delete" data-theme-icon-remove <?php echo $value > 0 ? '' : 'hidden'; ?> style="margin-left:12px;">
			<?php esc_html_e( 'Удалить', 'realtrigel' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Render theme settings page.
 *
 * @return void
 */
function realtrigel_render_theme_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Настройки темы', 'realtrigel' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'realtrigel_theme_settings' );
			do_settings_sections( 'realtrigel-theme-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Get stored theme icon attachment ID.
 *
 * @param string $key Icon key.
 * @return int
 */
function realtrigel_get_theme_icon_id( string $key ): int {
	$values = get_option( 'realtrigel_theme_icons', array() );

	return isset( $values[ $key ] ) ? max( 0, (int) $values[ $key ] ) : 0;
}

/**
 * Get stored theme icon URL.
 *
 * @param string $key  Icon key.
 * @param string $size Image size.
 * @return string
 */
function realtrigel_get_theme_icon_url( string $key, string $size = 'thumbnail' ): string {
	$attachment_id = realtrigel_get_theme_icon_id( $key );

	if ( $attachment_id <= 0 ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );

	return is_string( $url ) ? $url : '';
}
