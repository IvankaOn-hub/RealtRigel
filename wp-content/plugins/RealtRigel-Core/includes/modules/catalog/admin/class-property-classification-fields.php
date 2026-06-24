<?php
/**
 * Property classification fields metabox and save handlers.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Property_Classification_Fields {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'rr_property_classification_fields_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'rr_property_classification_fields_nonce';

	/**
	 * Validation error query arg.
	 */
	private const ERROR_QUERY_ARG = 'rr_catalog_required_fields';

	/**
	 * Missing fields query arg.
	 */
	private const MISSING_QUERY_ARG = 'rr_catalog_missing_fields';

	/**
	 * Post meta key for persisted validation notice.
	 */
	private const ERROR_META_KEY = '_rr_catalog_required_fields_error';

	/**
	 * Requirements validator.
	 *
	 * @var RR_Catalog_Requirements_Validator
	 */
	private RR_Catalog_Requirements_Validator $requirements_validator;

	/**
	 * Taxonomy fields config.
	 *
	 * @var array<string,array<string,string>>
	 */
	private array $fields = array();

	/**
	 * Whether current save failed validation.
	 *
	 * @var bool
	 */
	private bool $validation_failed = false;

	/**
	 * Missing field labels for current request.
	 *
	 * @var string[]
	 */
	private array $missing_fields = array();

	/**
	 * Constructor.
	 *
	 * @param RR_Catalog_Requirements_Validator $requirements_validator Requirements validator.
	 */
	public function __construct( RR_Catalog_Requirements_Validator $requirements_validator ) {
		$this->requirements_validator = $requirements_validator;
		$this->fields                 = array(
			RR_Property_Type_Taxonomy::TAXONOMY      => array(
				'input_name'  => 'rr_property_type_term_id',
				'label'       => __( 'Тип недвижимости', 'realtrigel-core' ),
				'placeholder' => __( 'Не выбрано', 'realtrigel-core' ),
			),
			RR_Property_Deal_Type_Taxonomy::TAXONOMY => array(
				'input_name'  => 'rr_property_deal_type_term_id',
				'label'       => __( 'Тип сделки', 'realtrigel-core' ),
				'placeholder' => __( 'Не выбрано', 'realtrigel-core' ),
			),
		);
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'handle_save_post' ), 10, 2 );
		add_action( 'save_post_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'enforce_required_fields' ), 100, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'validate_before_save' ), 10, 2 );
		add_filter( 'rest_pre_insert_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'validate_rest_before_save' ), 10, 2 );
		add_filter( 'redirect_post_location', array( $this, 'append_validation_error_to_redirect' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'render_validation_notice' ) );
		add_action( 'admin_head-post.php', array( $this, 'render_editor_hide_styles' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'render_editor_hide_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register metabox.
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		add_meta_box(
			'rr-property-classification',
			__( 'Классификация', 'realtrigel-core' ),
			array( $this, 'render_metabox' ),
			RR_Property_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="rr-classification-metabox">';

		foreach ( $this->fields as $taxonomy => $field ) {
			$this->render_select_field(
				$taxonomy,
				$field['input_name'],
				$field['label'],
				$field['placeholder'],
				$this->get_selected_term_id( $post->ID, $taxonomy )
			);
		}

		echo '</div>';

		$this->render_styles();
	}

	/**
	 * Save handler.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function handle_save_post( int $post_id, \WP_Post $post ): void {
		if ( ! $this->should_save_taxonomies( $post_id, $post ) ) {
			return;
		}

		foreach ( $this->fields as $taxonomy => $field ) {
			$term_id = $this->get_submitted_term_id( $field['input_name'] );

			if ( $term_id <= 0 ) {
				continue;
			}

			wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, false );
		}
	}

	/**
	 * Enforce required field assignments for all save flows.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function enforce_required_fields( int $post_id, \WP_Post $post ): void {
		if ( ! $this->should_enforce_requirements( $post_id, $post ) ) {
			return;
		}

		// Gutenberg creates/updates the post via REST before metabox payloads land.
		if ( $this->requirements_validator->is_rest_request() && ! $this->requirements_validator->has_editor_submission() ) {
			return;
		}

		$missing_fields = $this->requirements_validator->has_editor_submission()
			? $this->requirements_validator->get_missing_fields_from_request_or_post( $post_id )
			: $this->requirements_validator->get_missing_fields_for_post( $post_id );

		if ( empty( $missing_fields ) ) {
			delete_post_meta( $post_id, self::ERROR_META_KEY );
			return;
		}

		$this->validation_failed = true;
		$this->missing_fields    = $missing_fields;
		update_post_meta( $post_id, self::ERROR_META_KEY, wp_json_encode( $missing_fields ) );

		if ( in_array( $post->post_status, array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
			return;
		}

		remove_action( 'save_post_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'enforce_required_fields' ), 100 );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		add_action( 'save_post_' . RR_Property_Post_Type::POST_TYPE, array( $this, 'enforce_required_fields' ), 100, 2 );
	}

	/**
	 * Validate required fields before the standard editor save writes the post.
	 *
	 * @param array<string,mixed> $data    Sanitized post data.
	 * @param array<string,mixed> $postarr Raw post data.
	 *
	 * @return array<string,mixed>
	 */
	public function validate_before_save( array $data, array $postarr ): array {
		if ( ! isset( $data['post_type'] ) || RR_Property_Post_Type::POST_TYPE !== $data['post_type'] ) {
			return $data;
		}

		if ( $this->requirements_validator->is_meta_box_loader_request() ) {
			return $data;
		}

		if ( $this->requirements_validator->is_background_save( $postarr ) ) {
			return $data;
		}

		if ( ! $this->requirements_validator->has_editor_submission() ) {
			return $data;
		}

		$missing_fields = $this->requirements_validator->get_missing_fields_from_request();

		if ( empty( $missing_fields ) ) {
			return $data;
		}

		wp_die(
			esc_html(
				sprintf(
					/* translators: %s: comma separated field labels. */
					__( 'Перед сохранением объекта заполните: %s.', 'realtrigel-core' ),
					implode( ', ', $missing_fields )
				)
			),
			esc_html__( 'Не заполнены обязательные поля', 'realtrigel-core' ),
			array(
				'response'  => 400,
				'back_link' => true,
			)
		);
	}

	/**
	 * Validate required fields for REST saves before insert/update.
	 *
	 * @param \WP_Post|\WP_Error $prepared_post Prepared post object.
	 * @param \WP_REST_Request   $request       Request object.
	 *
	 * @return \WP_Post|\WP_Error
	 */
	public function validate_rest_before_save( $prepared_post, \WP_REST_Request $request ) {
		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$missing_fields = $this->requirements_validator->get_missing_fields_from_rest_request( $request );

		if ( empty( $missing_fields ) ) {
			return $prepared_post;
		}

		return new \WP_Error(
			'rr_catalog_required_fields_missing',
			sprintf(
				/* translators: %s: comma separated field labels. */
				__( 'Не заполнены обязательные поля: %s.', 'realtrigel-core' ),
				implode( ', ', $missing_fields )
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Append validation details to redirect URL.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $post_id  Post ID.
	 *
	 * @return string
	 */
	public function append_validation_error_to_redirect( string $location, int $post_id ): string {
		if ( ! $this->validation_failed || $post_id <= 0 ) {
			return $location;
		}

		$location = add_query_arg( self::ERROR_QUERY_ARG, '1', $location );

		if ( ! empty( $this->missing_fields ) ) {
			$location = add_query_arg(
				self::MISSING_QUERY_ARG,
				rawurlencode( implode( '|', $this->missing_fields ) ),
				$location
			);
		}

		return $location;
	}

	/**
	 * Render validation notice.
	 *
	 * @return void
	 */
	public function render_validation_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || RR_Property_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$missing_fields = array();
		$post_id        = $this->get_current_post_id();

		if ( $post_id > 0 ) {
			$stored_error = get_post_meta( $post_id, self::ERROR_META_KEY, true );

			if ( is_string( $stored_error ) && '' !== $stored_error ) {
				$decoded = json_decode( $stored_error, true );

				if ( is_array( $decoded ) ) {
					$missing_fields = array_filter( array_map( 'strval', $decoded ) );
				}
			}
		}

		if ( empty( $missing_fields ) && ! isset( $_GET[ self::ERROR_QUERY_ARG ] ) ) {
			return;
		}

		if ( empty( $missing_fields ) && isset( $_GET[ self::MISSING_QUERY_ARG ] ) ) {
			$decoded = rawurldecode( sanitize_text_field( wp_unslash( $_GET[ self::MISSING_QUERY_ARG ] ) ) );

			if ( '' !== $decoded ) {
				$missing_fields = array_filter( array_map( 'trim', explode( '|', $decoded ) ) );
			}
		}

		$message = __( 'Объект не может быть сохранен или опубликован без обязательных полей.', 'realtrigel-core' );

		if ( ! empty( $missing_fields ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: comma separated field labels. */
				__( 'Заполните: %s.', 'realtrigel-core' ),
				implode( ', ', $missing_fields )
			);
		}

		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Render one select field.
	 *
	 * @param string $taxonomy    Taxonomy slug.
	 * @param string $input_name  Input field name.
	 * @param string $label       Field label.
	 * @param string $placeholder Placeholder label.
	 * @param int    $selected_id Selected term ID.
	 *
	 * @return void
	 */
	private function render_select_field(
		string $taxonomy,
		string $input_name,
		string $label,
		string $placeholder,
		int $selected_id
	): void {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		echo '<p class="rr-classification-field">';
		echo '<label for="' . esc_attr( $input_name ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		echo '<select id="' . esc_attr( $input_name ) . '" name="' . esc_attr( $input_name ) . '" required>';
		printf(
			'<option value="" disabled hidden %1$s>%2$s</option>',
			selected( $selected_id, 0, false ),
			esc_html( $placeholder )
		);

		foreach ( $terms as $term ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $term->term_id,
				selected( $selected_id, (int) $term->term_id, false ),
				esc_html( $term->name )
			);
		}

		echo '</select>';
		echo '</p>';
	}

	/**
	 * Render styles.
	 *
	 * @return void
	 */
	private function render_styles(): void {
		?>
		<style>
			.rr-classification-metabox {
				width: 100%;
				max-width: 100%;
			}

			.rr-classification-field {
				margin: 0 0 12px;
				width: 100%;
				max-width: 100%;
				min-width: 0;
				box-sizing: border-box;
			}

			.rr-classification-field:last-child {
				margin-bottom: 0;
			}

			.rr-classification-field label {
				display: block;
				margin-bottom: 4px;
			}

			.rr-classification-field select {
				width: 100%;
				max-width: 100%;
				min-width: 0;
				box-sizing: border-box;
			}

			.rr-classification-field select.rr-field-invalid,
			.rr-location-metabox.rr-field-invalid {
				border-color: #d63638 !important;
				box-shadow: 0 0 0 1px #d63638 !important;
			}

			.rr-location-metabox.rr-field-invalid {
				padding: 12px;
				border: 1px solid #d63638;
				border-radius: 4px;
			}

			.rr-field-error {
				margin-top: 6px;
				color: #d63638;
				font-size: 12px;
				line-height: 1.4;
			}
		</style>
		<?php
	}

	/**
	 * Hide default taxonomy panels in Gutenberg sidebar.
	 *
	 * @return void
	 */
	public function render_editor_hide_styles(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || RR_Property_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( $this->requirements_validator->is_meta_box_loader_request() ) {
			return;
		}

		?>
		<style>
			.interface-complementary-area .components-panel__body[data-panel="taxonomy-panel-property_location"],
			.interface-complementary-area .components-panel__body[data-panel="taxonomy-panel-property_type"],
			.interface-complementary-area .components-panel__body[data-panel="taxonomy-panel-property_deal_type"],
			.interface-complementary-area .components-panel__body[data-panel="taxonomy-panel-property_feature"] {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue editor-side validation and panel cleanup.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || RR_Property_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( $this->requirements_validator->is_meta_box_loader_request() ) {
			return;
		}

		$post_id         = $this->get_current_post_id();
		$validation_text = '';

		if ( $post_id > 0 ) {
			$stored_error = get_post_meta( $post_id, self::ERROR_META_KEY, true );

			if ( is_string( $stored_error ) && '' !== $stored_error ) {
				$decoded = json_decode( $stored_error, true );

				if ( is_array( $decoded ) && ! empty( $decoded ) ) {
					$validation_text = sprintf(
						/* translators: %s: comma separated field labels. */
						__( 'Ошибка публикации. Не заполнены обязательные поля: %s.', 'realtrigel-core' ),
						implode( ', ', array_filter( array_map( 'strval', $decoded ) ) )
					);
					delete_post_meta( $post_id, self::ERROR_META_KEY );
				}
			}
		}

		wp_enqueue_script( 'wp-edit-post' );

		$config = array(
			'persistedMessage' => $validation_text,
			'fieldLabels'      => array(
				'propertyType' => __( 'Тип недвижимости', 'realtrigel-core' ),
				'dealType'     => __( 'Тип сделки', 'realtrigel-core' ),
				'location'     => __( 'Локация', 'realtrigel-core' ),
			),
			'messages'         => array(
				'fieldRequired'     => __( 'Это поле обязательно.', 'realtrigel-core' ),
				'locationRequired'  => __( 'Локация обязательна для заполнения.', 'realtrigel-core' ),
				'saveBlockedPrefix' => __( 'Не заполнены обязательные поля:', 'realtrigel-core' ),
				'saveBlockedSuffix' => __( 'Объект не может быть сохранен.', 'realtrigel-core' ),
			),
		);

		$script = <<<'JS'
(function () {
	const config = CONFIG_PLACEHOLDER;
	const noticeId = 'rr-catalog-required-fields-notice';
	const lockKey = 'rr-catalog-required-fields';
	const requiredIds = ['rr_property_type_term_id', 'rr_property_deal_type_term_id'];
	let lastAlertMessage = '';
	let lastAlertAt = 0;
	let listenersBound = false;
	let currentNoticeMessage = '';
	let saveLocked = null;

	const getEditorDispatch = function () {
		return wp.data.dispatch('core/editor');
	};

	const getNoticeContainer = function () {
		return document.querySelector('.edit-post-layout__content')
			|| document.querySelector('.interface-interface-skeleton__content')
			|| document.body;
	};

	const getNoticeElement = function () {
		return document.getElementById(noticeId);
	};

	const clearGlobalNotice = function () {
		if (currentNoticeMessage === '') {
			return;
		}

		const existingNotice = getNoticeElement();
		if (existingNotice) {
			existingNotice.remove();
		}

		currentNoticeMessage = '';
	};

	const showGlobalNotice = function (message) {
		if (currentNoticeMessage === message) {
			return;
		}

		clearGlobalNotice();

		const container = getNoticeContainer();
		if (!container) {
			return;
		}

		const notice = document.createElement('div');
		notice.id = noticeId;
		notice.className = 'notice notice-error is-dismissible';
		notice.innerHTML = '<p></p>';
		notice.querySelector('p').textContent = message;
		container.prepend(notice);
		currentNoticeMessage = message;
	};

	const clearFieldError = function (field) {
		if (!field) {
			return;
		}

		field.classList.remove('rr-field-invalid');
		field.removeAttribute('aria-invalid');

		const wrapper = field.closest('.rr-classification-field');
		if (!wrapper) {
			return;
		}

		const error = wrapper.querySelector('.rr-field-error');
		if (error) {
			error.remove();
		}
	};

	const showFieldError = function (field, message) {
		if (!field) {
			return;
		}

		clearFieldError(field);
		field.classList.add('rr-field-invalid');
		field.setAttribute('aria-invalid', 'true');

		const wrapper = field.closest('.rr-classification-field');
		if (!wrapper) {
			return;
		}

		const error = document.createElement('p');
		error.className = 'rr-field-error';
		error.textContent = message;
		wrapper.appendChild(error);
	};

	const getLocationContainer = function () {
		return document.querySelector('.rr-location-metabox');
	};

	const clearLocationError = function () {
		const container = getLocationContainer();
		if (!container) {
			return;
		}

		container.classList.remove('rr-field-invalid');

		const error = container.querySelector('.rr-field-error');
		if (error) {
			error.remove();
		}
	};

	const showLocationError = function (message) {
		const container = getLocationContainer();
		if (!container) {
			return;
		}

		clearLocationError();
		container.classList.add('rr-field-invalid');

		const error = document.createElement('p');
		error.className = 'rr-field-error';
		error.textContent = message;
		container.appendChild(error);
	};

	const getLocationFieldNames = function () {
		return [
			'rr_location_existing_country',
			'rr_location_existing_region',
			'rr_location_existing_city',
			'rr_location_existing_district',
			'rr_location_new_country',
			'rr_location_new_region',
			'rr_location_new_city',
			'rr_location_new_district'
		];
	};

	const isLocationValid = function () {
		const countrySelect = document.querySelector('[name="rr_location_existing_country"]');
		const countryInput = document.querySelector('[name="rr_location_new_country"]');
		const countrySelectValue = countrySelect ? String(countrySelect.value || '').trim() : '';
		const countryInputValue = countryInput ? String(countryInput.value || '').trim() : '';

		if (countryInputValue !== '') {
			return true;
		}

		return countrySelectValue !== '' && countrySelectValue !== '0';
	};

	const showAttemptFeedback = function (missingFields) {
		const message = config.messages.saveBlockedPrefix + ' ' + missingFields.join(', ') + '. ' + config.messages.saveBlockedSuffix;
		const now = Date.now();

		showGlobalNotice(message);

		if (message === lastAlertMessage && (now - lastAlertAt) < 1200) {
			return;
		}

		lastAlertMessage = message;
		lastAlertAt = now;
		window.alert(message);
	};

	const validateEditorState = function (showErrors) {
		let isValid = true;
		const missingFields = [];

		requiredIds.forEach(function (fieldId) {
			const field = document.getElementById(fieldId);
			const fieldValid = !!field && field.value !== '';
			const fieldLabel = fieldId === 'rr_property_type_term_id'
				? config.fieldLabels.propertyType
				: config.fieldLabels.dealType;

			if (fieldValid) {
				clearFieldError(field);
				return;
			}

			isValid = false;
			missingFields.push(fieldLabel);
			if (showErrors) {
				showFieldError(field, config.messages.fieldRequired);
			}
		});

		if (isLocationValid()) {
			clearLocationError();
		} else {
			isValid = false;
			missingFields.push(config.fieldLabels.location);
			if (showErrors) {
				showLocationError(config.messages.locationRequired);
			}
		}

		const editorDispatch = getEditorDispatch();
		if (editorDispatch) {
			if (isValid && saveLocked !== false && typeof editorDispatch.unlockPostSaving === 'function') {
				editorDispatch.unlockPostSaving(lockKey);
				saveLocked = false;
			}

			if (!isValid && saveLocked !== true && typeof editorDispatch.lockPostSaving === 'function') {
				editorDispatch.lockPostSaving(lockKey);
				saveLocked = true;
			}
		}

		if (isValid) {
			clearGlobalNotice();
		} else if (showErrors) {
			showAttemptFeedback(missingFields);
		}

		return {
			isValid: isValid,
			missingFields: missingFields
		};
	};

	const bindFieldListeners = function () {
		if (listenersBound) {
			return true;
		}

		const propertyTypeField = document.getElementById('rr_property_type_term_id');
		const dealTypeField = document.getElementById('rr_property_deal_type_term_id');

		if (!propertyTypeField || !dealTypeField) {
			return false;
		}

		requiredIds.forEach(function (fieldId) {
			const field = document.getElementById(fieldId);
			if (!field) {
				return;
			}

			field.addEventListener('change', function () {
				validateEditorState(false);
			});
		});

		getLocationFieldNames().forEach(function (fieldName) {
			const field = document.querySelector('[name="' + fieldName + '"]');
			if (!field) {
				return;
			}

			const eventName = field.tagName === 'SELECT' ? 'change' : 'input';
			field.addEventListener(eventName, function () {
				validateEditorState(false);
			});
		});

		listenersBound = true;
		validateEditorState(false);
		return true;
	};

	const setupBindingsWhenReady = function () {
		if (bindFieldListeners()) {
			return;
		}

		const intervalId = window.setInterval(function () {
			if (!bindFieldListeners()) {
				return;
			}

			window.clearInterval(intervalId);
		}, 300);
	};

	wp.domReady(function () {
		const editorDispatch = getEditorDispatch();
		if (editorDispatch && typeof editorDispatch.removeEditorPanel === 'function') {
			editorDispatch.removeEditorPanel('taxonomy-panel-property_type');
			editorDispatch.removeEditorPanel('taxonomy-panel-property_deal_type');
			editorDispatch.removeEditorPanel('taxonomy-panel-property_location');
			editorDispatch.removeEditorPanel('taxonomy-panel-property_feature');
		}

		setupBindingsWhenReady();

		document.addEventListener('click', function (event) {
			const target = event.target.closest('button, a');
			if (!target) {
				return;
			}

			const selector = [
				'.editor-post-publish-button',
				'.editor-post-save-draft',
				'.editor-post-publish-panel__toggle',
				'.editor-post-saved-state',
				'.components-button.editor-post-publish-panel__toggle'
			].join(', ');

			if (!target.matches(selector)) {
				return;
			}

			validateEditorState(true);
		}, true);

		if (config.persistedMessage) {
			showGlobalNotice(config.persistedMessage);
			window.setTimeout(function () {
				window.alert(config.persistedMessage);
			}, 50);
		}
	});
})();
JS;

		$script = str_replace( 'CONFIG_PLACEHOLDER', wp_json_encode( $config ), $script );
		wp_add_inline_script( 'wp-edit-post', $script, 'after' );
	}

	/**
	 * Determine selected term ID for post/taxonomy pair.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return int
	 */
	private function get_selected_term_id( int $post_id, string $taxonomy ): int {
		$term_ids = wp_get_object_terms(
			$post_id,
			$taxonomy,
			array(
				'fields' => 'ids',
				'number' => 1,
			)
		);

		if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
			return 0;
		}

		return (int) $term_ids[0];
	}

	/**
	 * Determine whether taxonomy saving should run.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return bool
	 */
	private function should_save_taxonomies( int $post_id, \WP_Post $post ): bool {
		if ( ! $this->has_valid_nonce() ) {
			return false;
		}

		if ( $this->requirements_validator->is_background_save( array( 'ID' => $post_id ) ) ) {
			return false;
		}

		if ( RR_Property_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Determine whether required fields enforcement should run.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return bool
	 */
	private function should_enforce_requirements( int $post_id, \WP_Post $post ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( RR_Property_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Check nonce.
	 *
	 * @return bool
	 */
	private function has_valid_nonce(): bool {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		return wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Get submitted term ID.
	 *
	 * @param string $input_name Input field name.
	 *
	 * @return int
	 */
	private function get_submitted_term_id( string $input_name ): int {
		if ( ! isset( $_POST[ $input_name ] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_POST[ $input_name ] ) );
	}

	/**
	 * Get current post ID from request.
	 *
	 * @return int
	 */
	private function get_current_post_id(): int {
		if ( isset( $_GET['post'] ) ) {
			return absint( wp_unslash( $_GET['post'] ) );
		}

		if ( isset( $_GET['post_ID'] ) ) {
			return absint( wp_unslash( $_GET['post_ID'] ) );
		}

		return 0;
	}
}
